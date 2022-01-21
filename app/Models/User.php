<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use App\Exceptions\InvalidAccessTokenException;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'github_id',
        'username',
        'access_token',
        'scope',
        'avatar',
        'is_sponsor',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_sponsor' => 'boolean',
    ];

    public const AVAILABLE_SETTINGS = ['show_language_tags', 'autosave_notes'];

    protected $attributes = [
        'settings' => '{"show_language_tags": true, "autosave_notes": true}',
    ];

    protected static function booted()
    {
        static::deleting(function (self $user) {
            $user->revokeGrant();
            $user->tags->each->delete();
            $user->stars->each->delete();
        });
    }

    public function readSetting(string $name, $default = null)
    {
        if (array_key_exists($name, $this->settings)) {
            return $this->settings[$name];
        }

        return $default;
    }

    public function writeSetting(string $name, $value, bool $save = true): self
    {
        throw_if(!in_array($name, self::AVAILABLE_SETTINGS), new \Exception('Setting not available'));

        $this->settings = array_merge($this->settings, [$name => $value]);

        if ($save) {
            $this->save();
        }

        return $this;
    }

    public function getAccessTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setAccessTokenAttribute($value)
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function updateFromGitHubProfile($githubUser): self
    {
        $this->username = $githubUser->getNickname();
        $this->github_id = $githubUser->getId();

        if ($githubUser->getName()) {
            $this->name = $githubUser->getName();
        }
        $this->avatar = $githubUser->getAvatar();

        return $this;
    }

    public function revokeGrant(): self
    {
        $clientId = config('services.github.client_id');
        $clientSecret = config('services.github.client_secret');

        $response = Http::withBasicAuth($clientId, $clientSecret)
            ->withHeaders(['Accept' => 'application/vnd.github.v3+json'])
            ->delete("https://api.github.com/applications/{$clientId}/grant", ['access_token' => $this->access_token]);

        $this->update(['access_token' => null]);

        if ($response->getStatusCode() == 404) {
            throw new InvalidAccessTokenException();
        }

        return $this;
    }

    public function isSponsor(): bool
    {
        return (bool) $this->is_sponsor;
    }

    public function isNotSponsor(): bool
    {
        return ! (bool) $this->is_sponsor;
    }

    public function setSponsorshipStatus(boolean $isSponsor): self
    {
        $this->update(['is_sponsor' => $isSponsor ? now() : null]);

        return $this;
    }

    public function tags()
    {
        return $this->hasMany(Tag::class);
    }

    public function stars()
    {
        return $this->hasMany(Star::class);
    }

    public function smartFilters()
    {
        return $this->hasMany(SmartFilter::class);
    }

    public function limits()
    {
        return $this->isNotSponsor() ?
            config('limits') :
            collect(config('limits'))->map(fn($item, $key) => -1)->toArray();
    }
}
