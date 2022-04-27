<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: Origin, X-Requested-With, Content, Accept, Content-Type, Authorization");
header("Access-Control-Allow-Headers: GET, POST, PUT, DELETE, PATCH, OPTIONS");

?>

<?php if( have_posts() ) : while( have_posts() ) : the_post(); ?>
<?php the_content(); ?>
<?php endwhile; endif; ?>
