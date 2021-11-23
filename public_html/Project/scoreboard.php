<?php
    require(__DIR__ . "/../../partials/nav.php");
    // require_once(__DIR__ . "/../../../lib/functions.php");
?>

<h1>Scoreboards</h1>

<!-- <div class="container-fluid"> -->
<div>
    <form method="POST" action="#">
        <input type="submit" name="time" value="Weekly"/>
        <input type="submit" name="time" value="Monthly"/>
        <input type="submit" name="time" value="Lifetime"/>
    </form>
</div>
<!-- week month lifetime -->
<?php
    $duration = "week";  // default 
    if (isset($_POST["time"])){
        $duration = $_POST["time"];
    }
    if ($duration == "Weekly"){
        $duration = "week";
    } else if ($duration == "Monthly"){
        $duration = "month";
    } else if ($duration == "Lifetime"){
        $duration = "lifetime";
    }


    $results = get_top_10($duration);
    print(var_export($results, true));
    
    // foreach($results as $result){
    //     print($result);
    // }

?>