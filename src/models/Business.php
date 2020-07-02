<?php

namespace Abs\BusinessPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'businesses';
	protected $fillable = [
		'company_id',
		'code',
		'name',
		'company_code',
		'company_name',
		'folder_name',
	];

	protected static $excelColumnRules = [
		'Code' => [
			'table_column_name' => 'code',
			'rules' => [
				'required' => [
				],
			],
		],
		'Name' => [
			'table_column_name' => 'name',
			'rules' => [
				'required' => [
				],
			],
		],
		'Folder Name' => [
			'table_column_name' => 'folder_name',
			'rules' => [
				'required' => [
				],
			],
		],
		'Business Company Code' => [
			'table_column_name' => 'company_code',
			'rules' => [
			],
		],
		'Business Company Name' => [
			'table_column_name' => 'company_name',
			'rules' => [
			],
		],
	];

	// Getter & Setters --------------------------------------------------------------

	// Query Scopes --------------------------------------------------------------

	public function scopeFilterCode($query, $code) {
		$query->where('code', $code);
	}

	// Relations --------------------------------------------------------------

	public function regions() {
		return $this->belongsToMany('App\Region', 'region_business');
	}

	public function outlets() {
		return $this->belongsToMany('App\Outlet', 'business_outlet');
	}

	public function vendors() {
		return $this->belongsToMany('App\Vendor', 'business_vendor');
	}

	public function jobCards() {
		return $this->hasMany('App\JobCard');
	}

	public function business_outlet() {
		return $this->belongsToMany('App\Outlet', 'business_outlet', 'business_id', 'outlet_id');
	}

	public function business_user() {
		return $this->belongsToMany('App\User', 'business_user', 'business_id', 'user_id');
	}

	public static function list() {

		$business = Business::withTrashed()
			->select('businesses.id', 'businesses.name', 'businesses.folder_name', 'companies.name as company_name', DB::raw('IF(businesses.deleted_at,"InActive" ,"Active") as status'))
			->leftJoin('companies', 'companies.id', 'businesses.company_id')
			->where('companies.id', session('company_id'))
			->orderBy('businesses.id')
		;
		return $business;
	}

	public static function add() {

		$data['business'] = new Business();
		$user_company_id = Auth()->user()->company_id;
		/*$data['company_list'] = Company::where('id', $user_company_id)->pluck('name', 'id')->prepend('Select Company', '');*/
		$data['company_list'] = Company::pluck('name', 'id')->prepend('Select Company', '');

		return $data;
	}

	public static function edit($business_id) {

		$data['business'] = $business = Business::withTrashed()->where('id', $business_id)->first();
		$user_company_id = Auth()->user()->company_id;
		$data['company_list'] = Company::where('id', $business->company_id)->pluck('name', 'id')->prepend('Select Company', '');
		return $data;
	}

	/*public static function createFromCollection($records) {
			foreach ($records as $key => $record_data) {
				try {
					if (!$record_data->company) {
						continue;
					}
					$record = self::createFromObject($record_data);
				} catch (Exception $e) {
					dd($e);
				}
			}
		}

		public static function createFromObject($record_data) {

			$errors = [];
			$company = Company::where('code', $record_data->company)->first();
			if (!$company) {
				dump('Invalid Company : ' . $record_data->company);
				return;
			}

			$admin = $company->admin();
			if (!$admin) {
				dump('Default Admin user not found');
				return;
			}

			if (count($errors) > 0) {
				dump($errors);
				return;
			}

			$record = self::firstOrNew([
				'company_id' => $company->id,
				'code' => $record_data->code,
			]);
			$record->name = $record_data->name;
			$record->folder_name = $record_data->folder_name;
			$record->company_code = $record_data->company_code;
			$record->company_name = $record_data->company_name;
			$record->created_by = $admin->id;
			$record->save();
			return $record;
	*/
	public static function saveFromObject($record_data) {
		$record = [
			'Company Code' => $record_data->company_code,
			'Code' => $record_data->code,
			'Name' => $record_data->name,
			'Folder Name' => $record_data->folder_name,
			'Business Company Code' => $record_data->business_company_code,
			'Business Company Name' => $record_data->business_company_name,
		];
		return static::saveFromExcelArray($record);
	}

	public static function saveFromExcelArray($record_data) {
		try {
			$errors = [];
			$company = Company::where('code', $record_data['Company Code'])->first();
			if (!$company) {
				return [
					'success' => false,
					'errors' => ['Invalid Company : ' . $record_data['Company Code']],
				];
			}

			if (!isset($record_data['created_by'])) {
				$admin = $company->admin();

				if (!$admin) {
					return [
						'success' => false,
						'errors' => ['Default Admin user not found'],
					];
				}
				$created_by = $admin->id;
			} else {
				$created_by = $record_data['created_by'];
			}

			if (empty($record_data['Code'])) {
				$errors[] = 'Code is empty';
			}

			if (empty($record_data['Name'])) {
				$errors[] = 'Name is empty';
			}

			if (empty($record_data['Folder Name'])) {
				$errors[] = 'Name is empty';
			}

			if (count($errors) > 0) {
				return [
					'success' => false,
					'errors' => $errors,
				];
			}

			$record = Self::firstOrNew([
				'company_id' => $company->id,
				'code' => $record_data['Code'],
			]);

			$result = Self::validateAndFillExcelColumns($record_data, Static::$excelColumnRules, $record);
			if (!$result['success']) {
				return $result;
			}

			$record->company_id = $company->id;
			$record->created_by = $created_by;
			$record->save();
			return [
				'success' => true,
			];
		} catch (\Exception $e) {
			return [
				'success' => false,
				'errors' => [
					$e->getMessage(),
				],
			];
		}
	}
}
