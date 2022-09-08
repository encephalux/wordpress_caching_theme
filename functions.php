<?php
const _RUN_MODE_ = "production";
const _API_TOKEN_ = "api_token";
const _API_BASE_URL_ = (_RUN_MODE_ === "development" ? "http://localhost:3000":"https://notaire-tsakadi.tg")."/wordpress-caching";
const _AVAILABLE_POST_TYPES_ = ["post", "page"];

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

// { Theme supports }

add_theme_support("post-thumbnails");
add_theme_support("title-tag");

// { Actions }

function post_saved($_id, $_post, $_is_update) {
    if(!array_search($_post->post_type, _AVAILABLE_POST_TYPES_)) return;

    $status = $_post->post_status === "publish" ? "published":"not_published";

    $endpoint = "/". ($_is_update ? "update":"add");
    $data = [
        'post_type' => $_post->post_type,
        'id' => $_id,
        'slug' => $_post->post_name,
        'status' => $status
    ];

    if($_post->post_type !== "page") {
        $data["title"] = $_post->post_title;
        $data["_excerpt"] = $_post->post_excerpt;
    }

    excite($endpoint, $data);
}

function post_deleted($_id, $_post) {

  if($_post->post_type !== "post") {
    return;
  }
  
  $endpoint = "/delete";
  $data = [
    'post_type' => $_post->post_type,
    'id' => $_id
  ];

  excite($endpoint, $data);
}

add_action("save_post", "post_saved", 10, 3);
add_action("delete_post", "post_deleted", 10, 2);

// { Ajax }
const _EMPTY_DATA_ = [
    'status' => "OK",
    'content' => [
        'total' => 0,
        'list' => []
    ]
];

function load_articles() {
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Methods: Origin, X-Requested-With, Content, Accept, Content-Type, Authorization");
  header("Access-Control-Allow-Headers: GET, POST, PUT, DELETE, PATCH, OPTIONS");

  $page = $_POST['page'];
  $per_page = $_POST['per_page'] ?? 5;
  $offset = $_POST['offset'] ?? 0;
  $mode = $_POST['mode'] ?? "classic";

  if(preg_match("#[^0-9]#", $page)) {
    echo json_encode($mode === "classic" ? []:_EMPTY_DATA_);
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
    echo json_encode($mode === "classic" ? []:_EMPTY_DATA_);
  }

  exit;
}

add_action("wp_ajax_articles", "load_articles");
add_action("wp_ajax_nopriv_articles", "load_articles");

?>