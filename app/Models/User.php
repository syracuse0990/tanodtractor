<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Helpers\CommonModelTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use CommonModelTraits;


    public const MALE = 0;
    public const FEMALE = 1;

    const ROLE_ADMIN = 0;
    const ROLE_GOVERNMENT = 1;
    const ROLE_FARMER = 2;
    const ROLE_SUB_ADMIN = 3;
    const ROLE_SYSTEM_ADMIN = 9;

    public const STATE_INACTIVE = 0;
    public const STATE_ACTIVE = 1;
    public const STATE_DELETED = 2;

    static $rules = [];
    protected $perPage = 20;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'gender',
        'state_id',
        'email_verified_at',
        'email_verification_otp',
        'device_type',
        'fcm_token',
        'remember-token',
        'api_access_token',
        'api_token_time',
        'phone',
        'phone_country',
        'country_code',
        'role_id'
    ];
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Summary of boot
     * @return void
     */
    public static function boot()
    {
        parent::boot();
        self::bootMyModelTrait();
        self::bootLogsActivity();
    }

    public static function roleOptions()
    {
        return [
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_GOVERNMENT => 'Government',
            self::ROLE_FARMER => 'Farmer',
            self::ROLE_SUB_ADMIN => 'Sub Admin',
        ];
    }

    public function getRole()
    {
        $list = self::roleOptions();
        return !empty($list[$this->role_id]) ? $list[$this->role_id] : 'Not Defined';
    }

    public static function genderOptions()
    {
        return [
            self::MALE => 'Male',
            self::FEMALE => 'Female'
        ];
    }

    public function getGender()
    {
        $list = self::genderOptions();
        return !empty($list[$this->gender]) ? $list[$this->gender] : 'Not Defined';
    }

    public static function stateOptions()
    {
        return [
            self::STATE_INACTIVE => 'Inactive',
            self::STATE_ACTIVE => 'Active',
            self::STATE_DELETED => 'Deleted'
        ];
    }

    public function getStateLabel()
    {
        $list = [
            self::STATE_INACTIVE => 'Inactive',
            self::STATE_ACTIVE => 'Active',
            self::STATE_DELETED => 'Deleted'
        ];
        $label = [
            self::STATE_INACTIVE => 'secondary',
            self::STATE_ACTIVE => 'success',
            self::STATE_DELETED => 'danger'
        ];
        if (!empty($list[$this->state_id])) {
            return '<span class="badge bg-' . $label[$this->state_id] . '">' . $list[$this->state_id] . '</span>';
        }
        return '';
    }

    public function getState()
    {
        $list = self::stateOptions();
        return !empty($list[$this->state_id]) ? $list[$this->state_id] : 'Not Defined';
    }

    public static function generateEmailVerificationOtp()
    {
        $otp = mt_rand(1000, 9999);
        return $otp;

        // $otp = mt_rand(1000, 9999);
        // $count = self::where('email_verification_otp', $otp)->count();
        // if ($count > 0) {
        //     User::generateEmailVerificationOtp();
        // }
        // return $otp;
    }

    public static function generateOtp($phone_country, $phone, $user)
    {
        $otp = rand(1000, 9999);
        $userOtp = UserOtp::create([
            'phone_country' => $phone_country,
            'phone' => $phone,
            'otp' => $otp,
            'user_id' => $user ? $user->id : null
        ]);

        return $userOtp;
    }

    public function jsonResponse()
    {
        $currentUserGroup = $farmerGroup = null;
        $user_id = $this->id;
        $groups = TractorGroup::get();
        foreach ($groups as $group) {
            $farmerIds = $group->farmer_ids ? json_decode($group->farmer_ids, true) : [];
            if (in_array($user_id, $farmerIds)) {
                $currentUserGroup = $group;
            }
        }
        $farmerGroup = $currentUserGroup;

        $json['id'] = $this->id;
        $json['name'] = $this->name;
        $json['email'] = $this->email;
        $json['profile_image'] = $this->profile_photo_path;
        $json['country_code'] = $this->country_code;
        $json['phone_country'] = $this->phone_country;
        $json['phone_number'] = $this->phone;
        $json['role_id'] = $this->role_id;
        $json['gender'] = $this->gender;
        $json['state_id'] = $this->state_id;
        $json['phone_verification_otp'] = $this->phone_verification_otp;
        $json['email_verification_otp'] = $this->email_verification_otp;
        $json['email_verified_at'] = $this->email_verified_at;
        $json['created_at'] = !empty($this->created_at) ? $this->created_at->toDateTimeString() : null;
        $json['updated_at'] = !empty($this->updated_at) ? $this->updated_at->toDateTimeString() : null;
        $json['fcm_token'] = $this->fcm_token;
        $json['remember_token'] = $this->remember_token;
        $json['group_exists'] = $farmerGroup ? true : false;


        return $json;
    }

    public function otpResponse()
    {

        $json['id'] = $this->id;
        $json['email'] = $this->email;
        $json['email_verification_otp'] = $this->email_verification_otp;

        return $json;
    }
}
