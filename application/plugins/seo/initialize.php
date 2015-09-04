<?php

// initialize seo
include("seo.php");

$seo = new SEO(array(
    "title" => "SwiftDeal Online LLP",
    "keywords" => "swiftdeal online llp" ,
    "description" => "One of the premier internet company india",
    "author" => "",
    "robots" => "INDEX,FOLLOW",
    "photo" => CDN . "img/logo.png"
));

Framework\Registry::set("seo", $seo);