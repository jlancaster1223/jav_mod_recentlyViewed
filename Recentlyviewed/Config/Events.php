<?php

namespace Module\Recentlyviewed\Config;

use App\Libraries\System\Events;
use App\Libraries\Entities\Entity;
use App\Libraries\Admin\Permissions;

use Module\Recentlyviewed\Controllers\Frontend as RecentlyviewedFacade;

Events::on('inject_head', function($data) {
    $recentlyViewed = new RecentlyviewedFacade;

    // Check if there is a cookie set and if not set one
    $recentlyViewed->setCookie();
});

// Run a cron job every hour
Events::on('cron_jobs', function($jobs) {
    $jobs[] = [
        'name' => 'Recently Viewed Database Cleaner',
        'interval' => '3600',
//        'limiter' => 'maintenance_hours',
        'callback' => 'App\Libraries\System\Licensing::getLicenseFromApi'
    ];
    return $jobs;
});