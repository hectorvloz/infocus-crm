<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Support\TemplateMail;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'profile_photo_path',
        'profile_info',
        'password',
        'role',
        'cliente_id',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        $settings = TemplateMail::settings();
        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $this->email,
        ]);

        [$subject, $body] = TemplateMail::render(
            $settings,
            'template_password_reset_subject',
            'template_password_reset_body',
            'Restablecer contraseña - {empresa}',
            "Hola {nombre},\n\nRecibimos una solicitud para restablecer tu contraseña.\n\nUsa este enlace para continuar:\n{reset_url}\n\nSi no hiciste esta solicitud, ignora este correo.\n{empresa}",
            [
                'nombre' => (string) ($this->name ?? 'Usuario'),
                'reset_url' => $resetUrl,
                'empresa' => (string) ($settings['company_name'] ?? config('app.name', 'Infocus CRM')),
            ],
            [
                ['label' => 'Restablecer contraseña', 'url' => $resetUrl, 'kind' => 'primary'],
            ]
        );

        TemplateMail::send((string) $this->email, $subject, $body);
    }
}
