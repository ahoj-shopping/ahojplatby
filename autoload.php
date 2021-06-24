<?php

// Traits
include_once(dirname(__FILE__). "/classes/Traits/AhojPlatbyBaseModuleTrait.php");
include_once(dirname(__FILE__). "/classes/Traits/AhojPlatbyConfigModuleTrait.php");

// Classes
include_once(dirname(__FILE__). "/classes/AhojApi.php");
// include_once(dirname(__FILE__). "/classes/CustomStock.php");

// Controllers
include_once(dirname(__FILE__). "/controllers/front/ParentController.php");
// include_once(dirname(__FILE__). "/controllers/admin/ExtSuppliersController.php");

// Libs
include_once(dirname(__FILE__). "/lib/ahoj-pay.php");

// Adapters
include_once(dirname(__FILE__). "/classes/Adapter/ParentAdapterClass.php");
include_once(dirname(__FILE__). "/classes/Adapter/ZasielkovnaAdapterClass.php");
include_once(dirname(__FILE__). "/classes/Adapter/DpdAdapterClass.php");
include_once(dirname(__FILE__). "/classes/Adapter/BalikomatAdapterClass.php");
