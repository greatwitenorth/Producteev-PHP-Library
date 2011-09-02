<?php
include('producteev.php');

//Set your api secret here
$api_secret = '';

//Set your api key here
$api = '';

//Check to see if the form has been submitted, if not we'll display the form
if (isset($_POST['username']) && isset($_POST['password'])){
$username = $_POST['username'];
$password = $_POST['password'];

$p = new Producteev($api,$api_secret);
$p->SetReturnType('json');
$p->Set(array(
     'email'=>$username,
     'password'=>$password)
);

//Login as user defined above
$data = $p->Execute('users/login');

//Now that we're logged in lets get all our tasks
$data = $p->Execute('tasks/my_tasks');
echo '<pre>'.print_r($data,true).'</pre>';
} else {
?>

<form name="input" action="example.php" method="post">
	<p>Input your username and password to display a list of all your tasks.</p>
	Username: <input type="text" name="username" />
	</br>
	Password: <input type="password" name="password" />
	</br>
	<input type="submit" value="Submit" />
</form>

<?php } ?>