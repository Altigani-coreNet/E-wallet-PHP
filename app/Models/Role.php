<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use App\Traits\AppliesCountryScope;

class Role extends SpatieRole
{
    use AppliesCountryScope;
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'name',
		'guard_name',
		'country_id',
		'merchant_id',
	];

	public function country()
	{
		return $this->belongsTo(Country::class);
	}
}


