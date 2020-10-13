<?php

namespace Abs\BusinessPkg;
use Abs\BusinessPkg\Lob;
use Abs\BusinessPkg\Sbu;
use App\ActivityLog;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class SbuController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.admin_theme');
	}

	public function getSbuPkgList(Request $request) {
		$sbus = Sbu::withTrashed()
			->select(
				'sbus.id',
				'sbus.name',
				'lobs.name as lob',
				DB::raw('IF((businesses.name) IS NULL,"--",businesses.name) as business'),
				DB::raw('IF(sbus.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->leftjoin('lobs', 'lobs.id', 'sbus.lob_id')
			->leftjoin('businesses', 'businesses.id', 'sbus.business_id')
			->where('sbus.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->sbu_name)) {
					$query->where('sbus.name', 'LIKE', '%' . $request->sbu_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->lob_name)) {
					$query->where('lobs.name', 'LIKE', '%' . $request->lob_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->business_name)) {
					$query->where('businesses.name', 'LIKE', '%' . $request->business_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('sbus.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('sbus.deleted_at');
				}
			});
		// ->groupBy('sbus.id');
		// ->orderby('lobs.id', 'desc');

		return Datatables::of($sbus)
			->addColumn('name', function ($sbu) {
				$status = $sbu->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $sbu->name;
			})
			->addColumn('action', function ($sbu) {
				$edit_img = asset('public/theme/img/table/cndn/edit.svg');
				$delete_img = asset('public/theme/img/table/cndn/delete.svg');
				// $img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				// $img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				// $img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				// $img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-sbu')) {
					$output .= '<a href="#!/business-pkg/sbu/edit/' . $sbu->id . '" id = "" title="Edit"><img src="' . $edit_img . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $edit_img . '" onmouseout=this.src="' . $edit_img . '"></a>';
				}
				if (Entrust::can('delete-sbu')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_sbu" onclick="angular.element(this).scope().deleteSbu(' . $sbu->id . ')" title="Delete"><img src="' . $delete_img . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $delete_img . '" onmouseout=this.src="' . $delete_img . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getSbuPkgFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$sbu = new Sbu;
			$action = 'Add';
		} else {
			$sbu = Sbu::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['sbu'] = $sbu;
		$this->data['lobs'] = collect(Lob::where('company_id', Auth::user()->company_id)->select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select LOB']);
		$this->data['business_list'] = collect(Business::getList())->prepend(['id' => '', 'name' => 'Select Business']);
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function saveSbuPkg(Request $request) {
		// dd($request->all());
		try {
			$sbu_id = $request->id;
			$error_messages = [
				'name.required' => "SBU is required.",
				'name.unique' => "SBU is already taken.",
			];

			$validator = Validator::make($request->all(), [
				'name' => [
					'required',
					'unique:sbus,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id . ',lob_id,' . $request->lob_id,
					'max:255',
				],
			], $error_messages);

			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$sbu = new Sbu;
				$sbu->created_by = Auth::user()->id;
				$sbu->created_at = Carbon::now();
				$sbu->updated_at = NULL;
			} else {
				$sbu = Sbu::withTrashed()->find($request->id);
				$sbu->updated_by = Auth::user()->id;
				$sbu->updated_at = Carbon::now();
			}
			$sbu->fill($request->all());
			$sbu->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$sbu->deleted_at = Carbon::now();
				$sbu->deleted_by = Auth::user()->id;
			} else {
				$sbu->deleted_by = NULL;
				$sbu->deleted_at = NULL;
			}
			$sbu->save();

			$activity_log = new ActivityLog;
			$activity_log->date_time = Carbon::now();
			$activity_log->user_id = Auth::user()->id;
			$activity_log->entity_id = $sbu->id;
			$activity_log->entity_type_id = 361;
			$activity_log->activity_id = $sbu_id == NULL ? 280 : 281;
			$activity_log->details = json_encode($activity_log);
			$activity_log->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json(['success' => true, 'message' => ['Sbu Added Successfully']]);
			} else {
				return response()->json(['success' => true, 'message' => ['Sbu Updated Successfully']]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function deleteSbuPkg(Request $request) {
		DB::beginTransaction();
		try {
			$sbu = Sbu::withTrashed()->where('id', $request->id)->first();
			if ($sbu) {
				$activity_log = new ActivityLog;
				$activity_log->date_time = Carbon::now();
				$activity_log->user_id = Auth::user()->id;
				$activity_log->entity_id = $sbu->id;
				$activity_log->entity_type_id = 361;
				$activity_log->activity_id = 282;
				$activity_log->details = json_encode($activity_log);
				$activity_log->save();
				$sbu->forceDelete();
				DB::commit();
				return response()->json(['success' => true, 'message' => 'SBU Deleted Successfully']);
			} else {
				return response()->json(['success' => false, 'errors' => 'SBU not Found']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
