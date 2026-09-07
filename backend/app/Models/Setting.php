<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const CACHE_KEY = 'site-settings';

    public static function defaults(): array
    {
        return [
            'company_name' => 'Real Estate',
            'company_email' => 'hello@realestate.test',
            'company_phone' => '+1 555 010 2020',
            'company_address' => '123 Market Street, San Francisco, CA',
            'hero_title' => 'Explore Home that fits your dreams',
            'hero_subtitle' => 'Discover curated properties tailored to the way you live.',
            'about_title' => 'About Our Brand',
            'about_content' => 'Passionate about properties, dedicated to your vision. We help you find the perfect place to call home.',
            'years_of_experience' => '10',
            'projects_completed' => '12',
            'area_delivered' => '20',
            'ongoing_projects' => '25',
            'facebook_url' => '',
            'instagram_url' => '',
            'linkedin_url' => '',
            'seo_title' => 'Real Estate — Find Your Dream Home',
            'seo_description' => 'Browse curated property listings for sale and rent.',
        ];
    }

    public static function allSettings(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $stored = static::query()->pluck('value', 'key')->toArray();

            return array_merge(static::defaults(), $stored);
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
