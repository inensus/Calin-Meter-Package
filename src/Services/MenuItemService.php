<?php

namespace Inensus\CalinMeter\Services;


use App\Models\MenuItems;

class MenuItemService
{

    private $menuItems;

    public function __construct(MenuItems $menuItems)
    {
        $this->menuItems = $menuItems;
    }

    public function createMenuItems()
    {
        $menuItem = $this->menuItems->newQuery()->where('name', 'Calin Meter')->first();
        if ($menuItem) {
            return [];
        }

        $menuItem = [
            'name' =>'Calin Meter',
            'url_slug' =>'',
            'md_icon' =>'bolt'
        ];
        $subMenuItems= array();

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/calin-meters/calin-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem'=>$menuItem,'subMenuItems'=>$subMenuItems];

    }
}