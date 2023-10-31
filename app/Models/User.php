<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Sdkconsultoria\Core\Fields\TextField;
use Sdkconsultoria\Core\Models\Traits\BaseModel as TraitBaseModel;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use Notifiable;
    use HasRoles;
    use TraitBaseModel;

    public const DEFAULT_SEARCH = 'like';

    public const STATUS_DELETED = 0;

    public const STATUS_BLOCKED = 10;

    public const STATUS_DISABLED = 15;

    public const STATUS_CREATION = 20;

    public const STATUS_ACTIVE = 30;

    protected function fields()
    {
        return [
            TextField::make('name')
                ->label('Nombre')
                ->rules(['required'])
                ->filter([
                    'column' => [
                        'name',
                        'lastname',
                        'lastname_2',
                    ],
                ]),
            TextField::make('lastname')
                ->label('Apellido Paterno')
                ->rules(['required']),
            TextField::make('lastname_2')
                ->label('Apellido Materno')
                ->rules(['required']),
            TextField::make('email')
                ->label('Correo')
                ->rules(['required', 'email']),
            TextField::make('password')
                ->label('Contraseña')
                ->rules(['required', 'min:6', 'confirmed'])->hideOnIndex(),
            TextField::make('password_confirmation')
                ->label('Confirmar contraseña')
                ->rules(['min:6'])->hideOnIndex()->canBeSaved(false),
        ];
    }

    public function getTranslations(): array
    {
        return [
            'singular' => 'Usuario',
            'plural' => 'Usuarios',
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'lastname',
        'lastname_2',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
     * Guarda un modelo pero antes encrypta la contraseña si es necesario.
     */
    public function save(array $options = [])
    {
        if ($this->isDirty('password')) {
            $this->password = Hash::make($this->password);
        }

        parent::save($options);
    }
}
