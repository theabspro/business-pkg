<?php

namespace Abs\BusinessPkg;
use Abs\BusinessPkg\Sbu;
use App\Address;
use App\Country;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class SbuController extends Controller {

	public function __construct() {
	}

	public function getSbuList(Request $request) {
		$lobs = Sbu::withTrashed()
			->select(
				'lobs.id',
				'lobs.code',
				'lobs.name',
				DB::raw('IF(lobs.mobile_no IS NULL,"--",lobs.mobile_no) as mobile_no'),
				DB::raw('IF(lobs.email IS NULL,"--",lobs.email) as email'),
				DB::raw('IF(lobs.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->where('lobs.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->lob_code)) {
					$query->where('lobs.code', 'LIKE', '%' . $request->lob_code . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->lob_name)) {
					$query->where('lobs.name', 'LIKE', '%' . $request->lob_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->mobile_no)) {
					$query->where('lobs.mobile_no', 'LIKE', '%' . $request->mobile_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->email)) {
					$query->where('lobs.email', 'LIKE', '%' . $request->email . '%');
				}
			})
			->orderby('lobs.id', 'desc');

		return Datatables::of($lobs)
			->addColumn('code', function ($lob) {
				$status = $lob->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $lob->code;
			})
			->addColumn('action', function ($lob) {
				$edit_img = asset('public/theme/img/table/cndn/edit.svg');
				$delete_img = asset('public/theme/img/table/cndn/delete.svg');
				return '
					<a href="#!/business-pkg/lob/edit/' . $lob->id . '">
						<img src="' . $edit_img . '" alt="View" class="img-responsive">
					</a>
					<a href="javascript:;" data-toggle="modal" data-target="#delete_lob"
					onclick="angular.element(this).scope().deleteSbu(' . $lob->id . ')" dusk = "delete-btn" title="Delete">
					<img src="' . $delete_img . '" alt="delete" class="img-responsive">
					</a>
					';
			})
			->make(true);
	}

	public function getSbuFormData($id = NULL) {
		if (!$id) {
			$lob = new Sbu;
			$address = new Address;
			$action = 'Add';
		} else {
			$lob = Sbu::withTrashed()->find($id);
			$address = Address::where('address_of_id', 24)->where('entity_id', $id)->first();
			if (!$address) {
				$address = new Address;
			}
			$action = 'Edit';
		}
		$this->data['country_list'] = $country_list = Collect(Country::select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Country']);
		$this->data['lob'] = $lob;
		$this->data['address'] = $address;
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function saveSbu(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Sbu Code is Required',
				'code.max' => 'Maximum 255 Characters',
				'code.min' => 'Minimum 3 Characters',
				'code.unique' => 'Sbu Code is already taken',
				'name.required' => 'Sbu Name is Required',
				'name.max' => 'Maximum 255 Characters',
				'name.min' => 'Minimum 3 Characters',
				'gst_number.required' => 'GST Number is Required',
				'gst_number.max' => 'Maximum 191 Numbers',
				'mobile_no.max' => 'Maximum 25 Numbers',
				// 'email.required' => 'Email is Required',
				'address_line1.required' => 'Address Line 1 is Required',
				'address_line1.max' => 'Maximum 255 Characters',
				'address_line1.min' => 'Minimum 3 Characters',
				'address_line2.max' => 'Maximum 255 Characters',
				// 'pincode.required' => 'Pincode is Required',
				// 'pincode.max' => 'Maximum 6 Characters',
				// 'pincode.min' => 'Minimum 6 Characters',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'max:255',
					'min:3',
					'unique:lobs,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => 'required|max:255|min:3',
				'gst_number' => 'required|max:191',
				'mobile_no' => 'nullable|max:25',
				// 'email' => 'nullable',
				'address' => 'required',
				'address_line1' => 'required|max:255|min:3',
				'address_line2' => 'max:255',
				// 'pincode' => 'required|max:6|min:6',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$lob = new Sbu;
				$lob->created_by_id = Auth::user()->id;
				$lob->created_at = Carbon::now();
				$lob->updated_at = NULL;
				$address = new Address;
			} else {
				$lob = Sbu::withTrashed()->find($request->id);
				$lob->updated_by_id = Auth::user()->id;
				$lob->updated_at = Carbon::now();
				$address = Address::where('address_of_id', 24)->where('entity_id', $request->id)->first();
			}
			$lob->fill($request->all());
			$lob->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$lob->deleted_at = Carbon::now();
				$lob->deleted_by_id = Auth::user()->id;
			} else {
				$lob->deleted_by_id = NULL;
				$lob->deleted_at = NULL;
			}
			$lob->gst_number = $request->gst_number;
			$lob->axapta_location_id = $request->axapta_location_id;
			$lob->save();

			if (!$address) {
				$address = new Address;
			}
			$address->fill($request->all());
			$address->company_id = Auth::user()->company_id;
			$address->address_of_id = 24;
			$address->entity_id = $lob->id;
			$address->address_type_id = 40;
			$address->name = 'Primary Address';
			$address->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json(['success' => true, 'message' => ['Sbu Details Added Successfully']]);
			} else {
				return response()->json(['success' => true, 'message' => ['Sbu Details Updated Successfully']]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	public function deleteSbu($id) {
		$delete_status = Sbu::withTrashed()->where('id', $id)->forceDelete();
		if ($delete_status) {
			$address_delete = Address::where('address_of_id', 24)->where('entity_id', $id)->forceDelete();
			return response()->json(['success' => true]);
		}
	}
}