<?php
require_once(__DIR__ . "/../../partials/nav.php");
require_once(__DIR__ . "/show_scores.php");
// is_logged_in(true);

$user_id = se($_GET, "id", get_user_id(), false);
error_log("user id $user_id");
$isMe = $user_id === get_user_id();
$edit = !!se($_GET, "edit", false, false); //if key is present allow edit, otherwise no edit
if ($user_id < 1) {
    flash("Invalid user", "danger");
    redirect("home.php");
    //die(header("Location: home.php"));
}
?>
<?php
if (isset($_POST["save"]) && $isMe && $edit) {
    $db = getDB();
    $email = se($_POST, "email", null, false);
    $username = se($_POST, "username", null, false);
    $visibility = !!se($_POST, "visibility", false, false) ? 1 : 0;
    $hasError = false;
    //sanitize
    $email = sanitize_email($email);
    //validate
    if (!is_valid_email($email)) {
        flash("Invalid email address", "danger");
        $hasError = true;
    }
    if (!preg_match('/^[a-z0-9_-]{3,16}$/i', $username)) {
        flash("Username must only be alphanumeric and can only contain - or _", "danger");
        $hasError = true;
    }
    if (!$hasError) {
        $params = [":email" => $email, ":username" => $username, ":id" => get_user_id(), ":vis" => $visibility];
        $stmt = $db->prepare("UPDATE Users set email = :email, username = :username, visibility = :vis where id = :id");
        try {
            $stmt->execute($params);
        } catch (Exception $e) {
            users_check_duplicate($e->errorInfo);
        }
    }
    //select fresh data from table
    $stmt = $db->prepare("SELECT id, email, IFNULL(username, email) as `username` from Users where id = :id LIMIT 1");
    try {
        $stmt->execute([":id" => get_user_id()]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            //$_SESSION["user"] = $user;
            $_SESSION["user"]["email"] = $user["email"];
            $_SESSION["user"]["username"] = $user["username"];
        } else {
            flash("User doesn't exist", "danger");
        }
    } catch (Exception $e) {
        flash("An unexpected error occurred, please try again", "danger");
        //echo "<pre>" . var_export($e->errorInfo, true) . "</pre>";
    }


    //check/update password
    $current_password = se($_POST, "currentPassword", null, false);
    $new_password = se($_POST, "newPassword", null, false);
    $confirm_password = se($_POST, "confirmPassword", null, false);
    if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
        if ($new_password === $confirm_password) {
            //TODO validate current
            $stmt = $db->prepare("SELECT password from Users where id = :id");
            try {
                $stmt->execute([":id" => get_user_id()]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if (isset($result["password"])) {
                    if (password_verify($current_password, $result["password"])) {
                        $query = "UPDATE Users set password = :password where id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->execute([
                            ":id" => get_user_id(),
                            ":password" => password_hash($new_password, PASSWORD_BCRYPT)
                        ]);

                        flash("Password reset", "success");
                    } else {
                        flash("Current password is invalid", "warning");
                    }
                }
            } catch (Exception $e) {
                echo "<pre>" . var_export($e->errorInfo, true) . "</pre>";
            }
        } else {
            flash("New passwords don't match", "warning");
        }
    }
}
?>

<?php
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
    $public = se($r, "visibility", 0, false) > 0;
    if (!$public && !$isMe) {
        flash("User's profile is private", "warning");
        redirect("home.php");
        //die(header("Location: home.php"));
    }
} catch (Exception $e) {
    echo "<pre>" . var_export($e->errorInfo, true) . "</pre>";
}
?>

<div class="container-fluid">
    <h1>Profile</h1>
    <?php if ($isMe) : ?>
        <?php if ($edit) : ?>
            <a class="btn btn-primary" href="?">View</a>
        <?php else : ?>
            <a class="btn btn-primary" href="?edit=true">Edit</a>
        <?php endif; ?>
    <?php endif; ?>
    <div>
        <?php 
            $db = getDB();
            $stmt = $db->prepare("SELECT user_id, score, modified FROM Scores ORDER BY modified DESC LIMIT 11");
            $stmt->execute(array());
            // $user_id = get_user_id();
        ?>
        <h3>Score History</h3>
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
    <?php if (!$edit) : ?>
        <div>Username: <?php se($username); ?></div>
        <div>Joined: <?php se($created); ?></div>
        <!-- TODO any other public info -->
    <?php endif; ?>

<?php if ($isMe && $edit) : ?>
    <form method="POST" onsubmit="return validate(this);">
        <div class="mb-3">
            <div class="form-check form-switch">
                <input name="visibility" class="form-check-input" type="checkbox" id="flexSwitchCheckDefault" <?php if ($public) echo "checked"; ?>>
                <label class="form-check-label" for="flexSwitchCheckDefault">Make Profile Public</label>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input class="form-control" type="email" name="email" id="email" value="<?php se($email); ?>" />
        </div>
        <div class="mb-3">
            <label class="form-label" for="username">Username</label>
            <input class="form-control" type="text" name="username" id="username" value="<?php se($username); ?>" />
        </div>
        <!-- DO NOT PRELOAD PASSWORD -->
        <div class="mb-3">Password Reset</div>
        <div class="mb-3">
            <label class="form-label" for="cp">Current Password</label>
            <input class="form-control" type="password" name="currentPassword" id="cp" />
        </div>
        <div class="mb-3">
            <label class="form-label" for="np">New Password</label>
            <input class="form-control" type="password" name="newPassword" id="np" />
        </div>
        <div class="mb-3">
            <label class="form-label" for="conp">Confirm Password</label>
            <input class="form-control" type="password" name="confirmPassword" id="conp" />
        </div>
        <input type="submit" class="mt-3 btn btn-primary" value="Update Profile" name="save" />
    </form>
<?php endif; ?>
<script>
    function validate(form) {
        let pw = form.newPassword.value;
        let con = form.confirmPassword.value;
        let isValid = true;
        //TODO add other client side validation....

        //example of using flash via javascript
        //find the flash container, create a new element, appendChild
        if (pw !== con) {
            //find the container
            let flash = document.getElementById("flash");
            //create a div (or whatever wrapper we want)
            let outerDiv = document.createElement("div");
            outerDiv.className = "row justify-content-center";
            let innerDiv = document.createElement("div");
            //apply the CSS (these are bootstrap classes which we'll learn later)
            innerDiv.className = "alert alert-warning";
            //set the content
            innerDiv.innerText = "Password and Confirm password must match";
            outerDiv.appendChild(innerDiv);
            //add the element to the DOM (if we don't it merely exists in memory)
            flash.appendChild(outerDiv);
            flash("Password and Confirm password must match", "warning");
            isValid = false;
        }
        return isValid;
    }
</script>
<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>