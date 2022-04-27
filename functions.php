<?php
const _RUN_MODE_ = "production";
const _API_TOKEN_ = "api_token";
const _API_BASE_URL_ = _RUN_MODE_ === "development" ? "http://localhost:3000":"https://notaire-tsakadi.tg";

function excite(string $_endpoint, array $_data=[]) {
  $data = json_encode(array_merge(['token' => _API_TOKEN_], $_data));
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, _API_BASE_URL_.$_endpoint);
  curl_setopt($curl, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
  ]);
  curl_setopt($curl, CURLOPT_POST, 1);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

  $result = curl_exec($curl);
  curl_close($curl);

  return $result;
}

function countPublished() {
    global $wpdb;
    return $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'post' and post_status = 'publish'" );
}

// { Theme supports }

add_theme_support("post-thumbnails");
add_theme_support("title-tag");

// { Actions }

function postSaved($_id, $_post, $_isUpdate) {

    if($_post->post_type !== "post" && $_post->post_type !== "page") return;
    $status = $_post->post_status === "publish" ? "published":"not_published";

    $endpoint = "/api/". $_post->post_type ."/". ($_isUpdate ? "update":"add");
    $data = ($_post->post_type === "page") ? [
        'id' => $_id,
        'slug' => $_post->post_name,
        'status' => $status
    ]:[
        'id' => $_id,
        'title' => $_post->post_title,
        'slug' => $_post->post_name,
        'excerpt' => $_post->post_excerpt,
        'status' => $status
    ];

    excite($endpoint, $data);
}

function postDeleted($_id, $_post) {

  if($_post->post_type !== "post") {
    return;
  }
  
  $endpoint = "/api/post/delete";
  $data = [
    'id' => $_id
  ];

  excite($endpoint, $data);
}

add_action("save_post", "postSaved", 10, 3);
add_action("delete_post", "postDeleted", 10, 2);

// { Ajax }

function loadArticles() {
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Methods: Origin, X-Requested-With, Content, Accept, Content-Type, Authorization");
  header("Access-Control-Allow-Headers: GET, POST, PUT, DELETE, PATCH, OPTIONS");

  $page = $_POST['page'];
  $per_page = $_POST['per_page'] ?? 5;
  $offset = $_POST['offset'] ?? 0;
  $mode = $_POST['mode'] ?? "classic";

  if(preg_match("#[^0-9]#", $page)) {
    echo "[]";
    return;
  }

  $page = intval($page);

  $query = new WP_Query([
    'post_type' => ["post"],
    'post_status' => ["publish"],
    'posts_per_page' => $per_page,
    'offset' => ($per_page * ( $page -1 )) + $offset
  ]);

  if($query->have_posts()) {
    $stack = [];

    while($query->have_posts()) {
      $query->the_post();

      global $post;

      $stack[] = [
        'id' => $post->ID,
        'slug' => $post->post_name,
        'title' => $post->post_title,
        'excerpt' => $post->post_excerpt,
        'date' => $post->post_date
      ];
    }

    wp_reset_postdata();

    header("Content-Type: application/json");

    echo json_encode([
      'status' => "OK",
      'content' => $mode === "classic" ? $stack:[
          'total' => $query->found_posts,
          'list' => $stack
      ]
    ]);
  } else {
    echo "[]";
  }

  exit;
}

add_action("wp_ajax_articles", "loadArticles");
add_action("wp_ajax_nopriv_articles", "loadArticles");

// { Short code }

function linkShortCode ($_atts) {
  $atts = shortcode_atts([
    'url' => "",
    'titre' => ""
  ], $_atts, "lien");

  return empty($atts['url']) ? "":'<div class="nt util-link"><a href="'.$atts['url'].'" title="'.($atts['titre'] ? $atts['titre']:$atts['url']).'" target="_blank"><span class="icofont-link"></span><span>'.($atts['titre'] ? $atts['titre']:$atts['url']).'</span></a></div>';
}

add_shortcode("lien", "linkShortCode");

?>