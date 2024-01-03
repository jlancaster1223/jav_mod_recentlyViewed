<?php

namespace Module\Recentlyviewed\Models;

use CodeIgniter\Model;

class RecentlyViewedModel extends Model
{
    protected $table = 'account_recently_viewed';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'id',
        'date_created',
        'account_id',
        'products',
        'cookie_value',
    ];
}