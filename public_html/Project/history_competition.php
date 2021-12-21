<?php
require_once(__DIR__ . "/../../partials/nav.php");
?>
<?php
$user_id = se($_GET, "id", get_user_id(), false);
$email = get_user_email();
$username = get_username();
$created = "";
$public = false;
//$user_id = get_user_id(); //this is retrieved above now
//TODO pull any other public info you want
$db = getDB();
$stmt = $db->prepare("SELECT username, created, visibility from Users where id = :id");
try {
    $stmt->execute([":id" => $user_id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("user: " . var_export($r, true));
    $username = se($r, "username", "", false);
    $created = se($r, "created", "", false);
} catch (Exception $e) {
    echo "<pre>" . var_export($e->errorInfo, true) . "</pre>";
}
?>

<div class="container-fluid">
    <div>
        <?php 
            $db = getDB();
            $stmt = $db->prepare("SELECT user_id, score, modified FROM Scores ORDER BY modified DESC LIMIT 11");
            $stmt->execute(array());
            // $user_id = get_user_id();
        ?>
        <h3>Competition History</h3>
        <table class="table table-light">
            <thead>
                <th>Score</th>
                <th>Time</th>
            </thead>
            <tbody>
                <?php foreach ($stmt as $score) : ?>
                    <?php if($score["user_id"] === $user_id) : ?>
                        <tr>
                            <td><?=$score['score']?></td>
                            <td><?=$score['modified']?></td>
                        </tr>
                    <?php endif; ?>
                    <!-- <tr>
                        <td><?=$score['score']?></td>
                        <td><?=$score['modified']?></td>
                    </tr> -->
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>