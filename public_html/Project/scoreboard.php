<?php
    require(__DIR__ . "/../../partials/nav.php");
    // require_once(__DIR__ . "/../../../lib/functions.php");
?>

<h1>Scoreboards</h1>

<!-- <div class="container-fluid"> -->
<div>
    <form method="POST" action="#">
        <input type="submit" name="time" value="Week"/>
        <input type="submit" name="time" value="Month"/>
        <input type="submit" name="time" value="Lifetime"/>
    </form>
</div>
<!-- week month lifetime -->
<?php
    $duration = "week";  // default 
    if (isset($_POST["time"])){
        $duration = $_POST["time"];
    }
    if ($duration == "Week"){
        $duration = "week";
    } else if ($duration == "Month"){
        $duration = "month";
    } else if ($duration == "Lifetime"){
        $duration = "lifetime";
    }

    $results = get_top_10($duration);
    // print(var_export($results, true));
    
?>

<table class="table table-light">
    <thead>
        <tr>
            <th scope="col">Username</th>
            <th scope="col">Score</th>
            <th scope="col">Date</th>
        </tr>
    </thead>
    <tbody>
        <?php
            $db = getDB();
            $stmt = $db->prepare("SELECT user_id, username, score, Scores.modified FROM Scores JOIN Users ON Scores.user_id = Users.id ORDER BY score DESC LIMIT 10");
            $stmt->execute(array());
            $user_id = get_user_id()
        ?>
                                                            
        <?php foreach($results as $row): ?>
            <tr>
                <td><?=$row['username']?></td>
                <td><?=$row['score']?></td>
                <td><?=$row['modified']?></td>
            </tr>
        <?php endforeach ?>

    </tbody>
</table>