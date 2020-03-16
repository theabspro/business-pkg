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
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('lobs.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('lobs.deleted_at');
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
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_lob" onclick="angular.element(this).scope().deleteLob(' . $lob->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete_active . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getLobPkgFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$lob = new Lob;
			$lob->sbus = [];
			$action = 'Add';
		} else {
			$lob = Lob::withTrashed()->where('id', $id)->with([
				'sbus',
			])->first();
			$action = 'Edit';
		}
		$this->data['lob'] = $lob;
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function saveLobPkg(Request $request) {
		// dd($request->all());
		try {
			$lob_id = $request->id;
			$error_messages = [
				'name.required' => 'Lob is Required',
				'name.max' => 'Maximum 255 Characters',
				'name.min' => 'Minimum 3 Characters',
				'name.unique' => 'Lob is already taken',
			];
			$validator = Validator::make($request->all(), [
				'name' => [
					'required:true',
					'max:255',
					'min:3',
					'unique:lobs,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			//VALIDATE UNIQUE FOR SBU
			if (isset($request->sbus) && !empty($request->sbus)) {
				$error_messages_1 = [
					'name.required' => 'SBU is required',
					'name.unique' => 'SBU is already taken',
				];

				foreach ($request->sbus as $sbu_key => $sbu) {
					$validator_1 = Validator::make($sbu, [
						'name' => [
							'unique:sbus,name,' . $sbu['id'] . ',id,company_id,' . Auth::user()->company_id,
							'required',
						],
					], $error_messages_1);

					if ($validator_1->fails()) {
						return response()->json(['success' => false, 'errors' => $validator_1->errors()->all()]);
					}

					//FIND DUPLICATE SBU
					foreach ($request->sbus as $search_key => $search_array) {
						if ($search_array['name'] == $sbu['name']) {
							if ($search_key != $sbu_key) {
								return response()->json(['success' => false, 'errors' => ['SBU is already taken']]);
							}
						}
					}
				}
			}

			DB::beginTransaction();
			if (!$request->id) {
				$lob = new Lob;
				$lob->created_by = Auth::user()->id;
				$lob->created_at = Carbon::now();
				$lob->updated_at = NULL;
			} else {
				$lob = Lob::withTrashed()->find($request->id);
				$lob->updated_by = Auth::user()->id;
				$lob->updated_at = Carbon::now();
			}
			// $lob->fill($request->all());
			$lob->company_id = Auth::user()->company_id;
			$lob->name = $request->name;
			if ($request->status == 'Inactive') {
				$lob->deleted_at = Carbon::now();
				$lob->deleted_by = Auth::user()->id;
			} else {
				$lob->deleted_by = NULL;
				$lob->deleted_at = NULL;
			}
			$lob->save();

			//DELETE COA-CODES
			if (!empty($request->sbu_removal_ids)) {
				$sbu_removal_ids = json_decode($request->sbu_removal_ids, true);
				Sbu::withTrashed()->whereIn('id', $sbu_removal_ids)->forcedelete();
			}

			if (isset($request->sbus) && !empty($request->sbus)) {
				foreach ($request->sbus as $key => $sbu_value) {
					$sbu = Sbu::withTrashed()->firstOrNew(['id' => $sbu_value['id']]);
					$sbu->company_id = Auth::user()->company_id;
					$sbu->fill($sbu_value);
					$sbu->lob_id = $lob->id;
					if ($sbu_value['status'] == 'Active') {
						$sbu->deleted_at = NULL;
						$sbu->deleted_by = NULL;
					} else {
						$sbu->deleted_at = date('Y-m-d H:i:s');
						$sbu->deleted_by = Auth::user()->id;
					}
					if (empty($sbu_value['id'])) {
						$sbu->created_by = Auth::user()->id;
						$sbu->created_at = Carbon::now();
						$sbu->updated_at = NULL;
					} else {
						$sbu->updated_by = Auth::user()->id;
						$sbu->updated_at = Carbon::now();
					}
					$sbu->save();
				}
			}
			//Activity Log
			$activity_log = new ActivityLog;
			$activity_log->date_time = Carbon::now();
			$activity_log->user_id = Auth::user()->id;
			$activity_log->entity_id = $lob->id;
			$activity_log->entity_type_id = 360;
			$activity_log->activity_id = $lob_id == null ? 280 : 281;
			$activity_log->details = json_encode($activity_log);
			$activity_log->save();

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
	public function deleteLobPkg(Request $request) {
		DB::beginTransaction();
		try {
			$lob = Lob::withTrashed()->where('id', $request->id)->first();
			if ($lob) {
				$activity_log = new ActivityLog;
				$activity_log->date_time = Carbon::now();
				$activity_log->user_id = Auth::user()->id;
				$activity_log->entity_id = $lob->id;
				$activity_log->entity_type_id = 360;
				$activity_log->activity_id = 282;
				$activity_log->details = json_encode($activity_log);
				$activity_log->save();
				$lob->forceDelete();
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Lob Deleted Successfully']);
			} else {
				return response()->json(['success' => false, 'errors' => 'LOB not Found']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
