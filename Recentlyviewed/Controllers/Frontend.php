<?php

namespace Module\Recentlyviewed\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Store\Product;

use Module\Recentlyviewed\Models\RecentlyViewedModel;

class Frontend extends BaseController {
    public function setCookie() {
        // Check if there is a cookie called javelin_recentlyViewed

        if(isset($_COOKIE['javelin_recentlyViewed'])) {
            // Check if the cookie value is in the database
            $recentlyViewedModel = new RecentlyViewedModel();
            $recentlyViewed = $recentlyViewedModel->where('cookie_value', $_COOKIE['javelin_recentlyViewed'])->first();

            if(!$recentlyViewed) {
                $recentlyViewedModel->insert([
                    'cookie_value' => $_COOKIE['javelin_recentlyViewed'],
                    'products' => json_encode([]),
                ]);
            }
        }


        if (!isset($_COOKIE['javelin_recentlyViewed']) && !isset($_SESSION['account_id'])) {
            $this->setNewCookie();
        } elseif(!isset($_COOKIE['javelin_recentlyViewed']) && isset($_SESSION['account_id'])) {
            // Check if there is a cookie with the same value in the database
            $recentlyViewedModel = new RecentlyViewedModel();
            $recentlyViewed = $recentlyViewedModel->where('account_id', $_SESSION['account_id'])->first();

            if ($recentlyViewed) {
                // Set the cookie value to the one in the database
                setcookie('javelin_recentlyViewed', $recentlyViewed['cookie_value'], time() + (86400 * 30), "/");
            } else {
                $this->setNewCookie();
            } 
        } elseif(isset($_COOKIE['javelin_recentlyViewed']) && !isset($_SESSION['account_id'])) {
            // Check if the cookie value is in the database
            $recentlyViewedModel = new RecentlyViewedModel();
            $recentlyViewed = $recentlyViewedModel
                ->where('cookie_value', $_COOKIE['javelin_recentlyViewed'])
                ->first();

            if($recentlyViewed && $recentlyViewed['account_id'] != 0) {
                // Set the cookie value to the one in the database
                $this->setNewCookie();
            }
        }

        // finally, if loggedin, get it update
        if(isset($_COOKIE['javelin_recentlyViewed']) && isset($_SESSION['account_id'])) {
            // Update the cookie value in the database
            $recentlyViewedModel = new RecentlyViewedModel();
            $recentlyViewedAccountSpecific = $recentlyViewedModel
                ->where('account_id', $_SESSION['account_id'])
                ->first();

            if($recentlyViewedAccountSpecific) {
                // Update the cookie on the local machine to what is in the database
                setcookie('javelin_recentlyViewed', $recentlyViewedAccountSpecific['cookie_value'], time() + (86400 * 30), "/");
            } else {
                $recentlyViewed = $recentlyViewedModel
                ->where('cookie_value', $_COOKIE['javelin_recentlyViewed'])
                ->where('account_id', '0')
                ->first();

                if($recentlyViewed) {
                    // Create a new entry and delete the old one
                    $recentlyViewedModel->insert([
                        'products' => $recentlyViewed['products'],
                        'cookie_value' => $recentlyViewed['cookie_value'],
                        'account_id' => $_SESSION['account_id'],
                    ]);
                    $recentlyViewedModel->where('id', $recentlyViewed['id'])->delete();
                }
            }
        }

    }

    public function addProduct($productID) {
        $recentlyViewedModel = new RecentlyViewedModel;
        $recentlyViewed = $recentlyViewedModel->where('cookie_value', $_COOKIE['javelin_recentlyViewed'])->first();

        $products = json_decode($recentlyViewed['products'], true);

        if (!in_array($productID, $products)) {
            array_unshift($products, $productID);
        }

        $recentlyViewedModel->where('id', $recentlyViewed['id'])->delete();
        $recentlyViewedModel->insert([
            'id' => $recentlyViewed['id'],
            'products' => json_encode($products),
            'cookie_value' => $recentlyViewed['cookie_value'],
            'account_id' => $recentlyViewed['account_id'],
        ]);
    }

    public function recentlyViewedProductArray($cookie_value, $number = null) {
        $recentlyViewedModel = new RecentlyViewedModel;
        $recentlyViewed = $recentlyViewedModel->where('cookie_value', $cookie_value)->first();

        $products = json_decode($recentlyViewed['products'], true);

        $product_return = [];
        $counter = 0;
        foreach($products as $product) {
            // Add a break in here if we only want to show a certain number of products
            if($number != null && $counter == $number) {
                break;
            }

            $productLib = new Product();
            $productLib->setId($product);
            $product_return[] = $productLib->getProduct();

            $counter++;
        }

        return $product_return;
    }


    // This is the cron job that will run every hour
    public static function cleanDatabase() {
        // Delete everything from the database where the date_creted column is older than 31 days
        $recentlyViewedModel = new RecentlyViewedModel;
        $recentlyViewedModel
            ->where(['date_created <', date('Y-m-d H:i:s', strtotime('-30 days'))])
            ->delete();
    }


    // Private functions for the other functions to work
    private function generateCookie() {
        return bin2hex(random_bytes(20));
    }
    private function checkCookie($cookie) {
        $recentlyViewedModel = new RecentlyViewedModel;
        $recentlyViewed = $recentlyViewedModel->where('cookie_value', $cookie)->first();

        if ($recentlyViewed) {
            return false;
        } else {
            return true;
        }
    }

    private function setNewCookie() {
        // Set one with a random 40 character string
        $uniqueCookie = $this->generateCookie();
        while($this->checkCookie($uniqueCookie) == false) {
            $uniqueCookie = $this->generateCookie();
        }

        $recentlyViewedModel = new RecentlyViewedModel;
        $recentlyViewedModel->insert([
            'products' => json_encode([]),
            'cookie_value' => $uniqueCookie,
        ]);
        setcookie('javelin_recentlyViewed', $uniqueCookie, time() + (86400 * 30), "/");
    }
}
