<?php

namespace Abs\BusinessPkg;
use Abs\BusinessPkg\Lob;
use App\Address;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class LobController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.admin_theme');
	}

	public function getLobPkgList(Request $request) {
		$lobs = Lob::withTrashed()
			->select(
				'lobs.id',
				'lobs.name',
				DB::raw('COUNT(sbus.id) as sbu_count'),
				DB::raw('IF(lobs.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->leftJoin('sbus', 'sbus.lob_id', 'lobs.id')
			->where('lobs.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->lob_name)) {
					$query->where('lobs.name', 'LIKE', '%' . $request->lob_name . '%');
				}
			})
			->groupBy('lobs.id')
		// ->get()
		// ->orderby('lobs.id', 'desc')
		;

		return Datatables::of($lobs)
			->addColumn('name', function ($lob) {
				$status = $lob->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $lob->name;
			})
			->addColumn('action', function ($lob) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-lob')) {
					$output .= '<a href="#!/business-pkg/lob/edit/' . $lob->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-lob')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#lob-filter-modal" onclick="angular.element(this).scope().deleteLob(' . $lob->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete_active . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getLobPkgFormData($id = NULL) {
		if (!$id) {
			$lob = new Lob;
			$action = 'Add';
		} else {
			$lob = Lob::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function saveLob(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Lob Code is Required',
				'code.max' => 'Maximum 255 Characters',
				'code.min' => 'Minimum 3 Characters',
				'code.unique' => 'Lob Code is already taken',
				'name.required' => 'Lob Name is Required',
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
				$lob = new Lob;
				$lob->created_by_id = Auth::user()->id;
				$lob->created_at = Carbon::now();
				$lob->updated_at = NULL;
				$address = new Address;
			} else {
				$lob = Lob::withTrashed()->find($request->id);
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
				return response()->json(['success' => true, 'message' => ['Lob Details Added Successfully']]);
			} else {
				return response()->json(['success' => true, 'message' => ['Lob Details Updated Successfully']]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	public function deleteLob($id) {
		$delete_status = Lob::withTrashed()->where('id', $id)->forceDelete();
		if ($delete_status) {
			$address_delete = Address::where('address_of_id', 24)->where('entity_id', $id)->forceDelete();
			return response()->json(['success' => true]);
		}
	}
}
