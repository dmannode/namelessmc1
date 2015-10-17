<?php 
/*
 *	Made by Samerton
 *  http://worldscapemc.co.uk
 *
 *  License: MIT
 */

// Set the page name for the active link in navbar
$page = "forum";

// User must be logged in to proceed
if(!$user->isLoggedIn()){
	Redirect::to('/forum');
	die();
}

$forum = new Forum();


if(!isset($_GET["tid"]) || !is_numeric($_GET["tid"])){
	Redirect::to('/forum/error/?error=not_exist');
	die();
} else {
	$topic_id = $_GET["tid"];
	$forum_id = $queries->getWhere('topics', array('id', '=', $topic_id));
	$forum_id = $forum_id[0]->forum_id;
}

if($user->canViewMCP($user->data()->id)){ // TODO: Change to permission based if statement
	if(Input::exists()) {
		if(Token::check(Input::get('token'))) {
			$validate = new Validate();
			$validation = $validate->check($_POST, array(
				'merge' => array(
					'required' => true
				)
			));
			$posts_to_move = $queries->getWhere('posts', array('topic_id', '=', $topic_id));
			if($validation->passed()){
				try {
					foreach($posts_to_move as $post_to_move){
						$queries->update('posts', $post_to_move->id, array(
							'topic_id' => Input::get('merge')
						));
					}
					$queries->delete('topics', array('id', '=' , $topic_id));

					// Update latest posts in categories
					$forum->updateForumLatestPosts();
					$forum->updateTopicLatestPosts();

					Redirect::to('/forum/view_topic/?tid=' . Input::get('merge'));
					die();
				} catch(Exception $e){
					die($e->getMessage());
				}
			} else {
				echo 'Error processing that action. <a href="/forum">Forum index</a>';
				die();
			}
		}
	}
} else {
	Redirect::to("/forum");
	die();
}

$token = Token::generate();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?php echo $sitename; ?> Forum - Merge Threads">
    <meta name="author" content="Samerton">
	<meta name="robots" content="noindex">
    <?php if(isset($custom_meta)){ echo $custom_meta; } ?>

	<title><?php echo $sitename; ?> &bull; <?php echo $navbar_language['forum'] . ' - ' . $forum_language['merge_thread']; ?></title>
	
	<?php
	// Generate header and navbar content
	require('core/includes/template/generate.php');
	?>
	
	<!-- Custom style -->
	<style>
	html {
		overflow-y: scroll;
	}
	</style>
	
  </head>
  <body>
	<?php
	// Load navbar
	$smarty->display('styles/templates/' . $template . '/navbar.tpl'); 
	?>
	
    <div class="container">
	  <h2><?php echo $forum_language['merge_thread']; ?></h2>
	  <p><?php echo $forum_language['merge_instructions']; ?></p>
	  <?php 
		$threads = $queries->getWhere('topics', array('forum_id', '=', $forum_id));
	  ?>
	  <form action="" method="post">
		<div class="form-group">
		  <label for="InputMerge"><?php echo $forum_language['merge_with']; ?></label>
		  <select class="form-control" id="InputMerge" name="merge">
		  <?php 
		  foreach($threads as $thread){
			if($thread->id !== $topic_id){
		  ?>
		  <option value="<?php echo $thread->id; ?>"><?php echo htmlspecialchars($thread->topic_title); ?></option>
		  <?php 
			}
		  } 
		  ?>
		  </select> 
		</div>
		<input type="hidden" name="token" value="<?php echo Token::generate(); ?>">
		<input type="submit" value="<?php echo $general_language['submit']; ?>" class="btn btn-default">
	  </form>
	</div>
	<?php 
	// Footer
	require('core/includes/template/footer.php');
	$smarty->display('styles/templates/' . $template . '/footer.tpl');
	  
	// Scripts 
	require('core/includes/template/scripts.php');
	?>
  </body>
</html>