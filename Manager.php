<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-18
 * Time: 11:40 PM
 */
include "DataParser.php";
include "DataFetcher.php";
include "util.php";

include "libs/DiDom/ClassAttribute.php";
include "libs/DiDom/Element.php";
include "libs/DiDom/Errors.php";
include "libs/DiDom/Query.php";
include "libs/DiDom/Document.php";
include "libs/DiDom/Encoder.php";
include "libs/DiDom/StyleAttribute.php";
include "libs/DiDom/Exceptions/InvalidSelectorException.php";

$dataFetcher = new DataFetcher();
$dataFetcher->fetch();