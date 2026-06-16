<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ACCOUNT_TYPE_PLAYER = 'player';
    public const ACCOUNT_TYPE_STAFF = 'staff';

    public const PLAYER_TIER_COMMON = 'common';
    public const PLAYER_TIER_BRONZE = 'bronze';
    public const PLAYER_TIER_SILVER = 'silver';
    public const PLAYER_TIER_GOLD = 'gold';
    public const PLAYER_TIER_PLATINUM = 'platinum';

    public const STAFF_ROLE_MODERATOR = 'moderator';
    public const STAFF_ROLE_GAME_ADMIN = 'game_admin';
    public const STAFF_ROLE_TECH_ADMIN = 'tech_admin';
    public const STAFF_ROLE_SUPER_ADMIN = 'super_admin';

    public const PERMISSION_PLAY_GAME = 'play.game';
    public const PERMISSION_CHAT_USE = 'chat.use';
    public const PERMISSION_CHAT_MODERATE = 'chat.moderate';
    public const PERMISSION_ROOM_JOIN = 'room.join';
    public const PERMISSION_ROOM_CREATE = 'room.create';
    public const PERMISSION_ROOM_MANAGE = 'room.manage';
    public const PERMISSION_TOURNAMENT_JOIN = 'tournament.join';

    public const PERMISSION_ADMIN_ACCESS = 'admin.access';
    public const PERMISSION_ADMIN_MODERATION = 'admin.moderation';
    public const PERMISSION_ADMIN_GAME = 'admin.game';
    public const PERMISSION_ADMIN_TECH = 'admin.tech';
    public const PERMISSION_ADMIN_USERS = 'admin.users';
    public const PERMISSION_ADMIN_SYSTEM = 'admin.system';

    public const ACCOUNT_TYPES = [
        self::ACCOUNT_TYPE_PLAYER,
        self::ACCOUNT_TYPE_STAFF,
    ];

    public const PLAYER_TIERS = [
        self::PLAYER_TIER_COMMON,
        self::PLAYER_TIER_BRONZE,
        self::PLAYER_TIER_SILVER,
        self::PLAYER_TIER_GOLD,
        self::PLAYER_TIER_PLATINUM,
    ];

    public const STAFF_ROLES = [
        self::STAFF_ROLE_MODERATOR,
        self::STAFF_ROLE_GAME_ADMIN,
        self::STAFF_ROLE_TECH_ADMIN,
        self::STAFF_ROLE_SUPER_ADMIN,
    ];

    public const DEFAULT_STAFF_PERMISSIONS = [
        self::STAFF_ROLE_MODERATOR => [
            self::PERMISSION_ADMIN_ACCESS,
            self::PERMISSION_ADMIN_MODERATION,
            self::PERMISSION_CHAT_MODERATE,
        ],

        self::STAFF_ROLE_GAME_ADMIN => [
            self::PERMISSION_ADMIN_ACCESS,
            self::PERMISSION_ADMIN_GAME,
            self::PERMISSION_ADMIN_MODERATION,
            self::PERMISSION_CHAT_MODERATE,
            self::PERMISSION_ROOM_MANAGE,
            self::PERMISSION_PLAY_GAME,
        ],

        self::STAFF_ROLE_TECH_ADMIN => [
            self::PERMISSION_ADMIN_ACCESS,
            self::PERMISSION_ADMIN_TECH,
            self::PERMISSION_ADMIN_SYSTEM,
        ],

        self::STAFF_ROLE_SUPER_ADMIN => [
            self::PERMISSION_ADMIN_ACCESS,
            self::PERMISSION_ADMIN_MODERATION,
            self::PERMISSION_ADMIN_GAME,
            self::PERMISSION_ADMIN_TECH,
            self::PERMISSION_ADMIN_USERS,
            self::PERMISSION_ADMIN_SYSTEM,
            self::PERMISSION_CHAT_MODERATE,
            self::PERMISSION_ROOM_MANAGE,
            self::PERMISSION_PLAY_GAME,
        ],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'account_type',
        'player_tier',
        'is_vip',
        'staff_role',
        'permissions',
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
            'is_vip' => 'boolean',
            'permissions' => 'array',
        ];
    }

    public function isPlayer(): bool
    {
        return $this->account_type === self::ACCOUNT_TYPE_PLAYER;
    }

    public function isStaff(): bool
    {
        return $this->account_type === self::ACCOUNT_TYPE_STAFF;
    }

    public function isVip(): bool
    {
        return $this->isPlayer() && (bool) $this->is_vip;
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function canPlayGame(): bool
    {
        return $this->isPlayer() || $this->hasPermission(self::PERMISSION_PLAY_GAME);
    }

    public function hasStaffRole(string|array $roles): bool
    {
        return in_array($this->staff_role, (array) $roles, true);
    }

    public function isModerator(): bool
    {
        return $this->hasStaffRole([
            self::STAFF_ROLE_MODERATOR,
            self::STAFF_ROLE_GAME_ADMIN,
            self::STAFF_ROLE_SUPER_ADMIN,
        ]);
    }

    public function isGameAdmin(): bool
    {
        return $this->hasStaffRole([
            self::STAFF_ROLE_GAME_ADMIN,
            self::STAFF_ROLE_SUPER_ADMIN,
        ]);
    }

    public function isTechAdmin(): bool
    {
        return $this->hasStaffRole([
            self::STAFF_ROLE_TECH_ADMIN,
            self::STAFF_ROLE_SUPER_ADMIN,
        ]);
    }

    public function isSuperAdmin(): bool
    {
        return $this->staff_role === self::STAFF_ROLE_SUPER_ADMIN;
    }

    public function accountTypeLabel(): string
    {
        return match ($this->account_type) {
            self::ACCOUNT_TYPE_STAFF => 'Staff',
            default => 'Spieler',
        };
    }

    public function playerTierLabel(): string
    {
        return match ($this->player_tier) {
            self::PLAYER_TIER_BRONZE => 'Bronze',
            self::PLAYER_TIER_SILVER => 'Silber',
            self::PLAYER_TIER_GOLD => 'Gold',
            self::PLAYER_TIER_PLATINUM => 'Platin',
            default => 'Common',
        };
    }

    public function staffRoleLabel(): string
    {
        return match ($this->staff_role) {
            self::STAFF_ROLE_MODERATOR => 'Moderator',
            self::STAFF_ROLE_GAME_ADMIN => 'Game Admin',
            self::STAFF_ROLE_TECH_ADMIN => 'Tech Admin',
            self::STAFF_ROLE_SUPER_ADMIN => 'Super Admin',
            default => 'Keine Staff-Rolle',
        };
    }

    public function accountDisplayRole(): string
    {
        if ($this->isStaff()) {
            return $this->staffRoleLabel().' ['.$this->playerTierLabel().']';
        }

        return 'Spieler ['.$this->playerTierLabel().']';
    }

    public static function defaultPermissionsForStaffRole(?string $staffRole): array
    {
        return self::DEFAULT_STAFF_PERMISSIONS[$staffRole] ?? [];
    }
}
