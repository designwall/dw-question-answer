<div class="suggest-question">
	<h3><?php _e('Latest 10 open questions awaiting for your answers','dwqa') ?></h3>
	<div class="dwqa-latest-questions">
		<?php $posts = get_posts( 'post_type=dwqa-question&posts_per_page=10' ); ?>
		<?php if( count($posts) > 0 ) : ?>
		<ul>
			<?php foreach ($posts as $post) : ?>
				<li><?php echo $post->post_title; ?></li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</div>
</div>