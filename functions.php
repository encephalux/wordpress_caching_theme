<?php
require_once "env.php";

function excite(string $_endpoint, array $_data = [])
{
    $data = json_encode(array_merge(['token' => _API_TOKEN_], $_data));
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, _API_BASE_URL_ . $_endpoint);
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

function post_saved($_id, $_post, $_is_update)
{
    if(array_search($_post->post_type, _AVAILABLE_POST_TYPES_) === !1) return;

    $status = $_post->post_status === "publish" ? "published" : "not_published";

    $endpoint = "/" . ($_is_update ? "update" : "add");
    $data = [
        'post_type' => $_post->post_type,
        'id' => $_id,
        'slug' => $_post->post_name,
        'title' => $_post->post_title,
        'status' => $status
    ];

    if ($_post->post_type !== "page") {
        $data["_excerpt"] = $_post->post_excerpt;
    }

    excite($endpoint, $data);
}

function post_deleted($_id, $_post)
{

    if ($_post->post_type !== "post") {
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

function load_articles()
{
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: Origin, X-Requested-With, Content, Accept, Content-Type, Authorization");
    header("Access-Control-Allow-Headers: GET, POST, PUT, DELETE, PATCH, OPTIONS");

    $page = $_POST['page'];
    $per_page = $_POST['per_page'] ?? 5;
    $offset = $_POST['offset'] ?? 0;
    $mode = $_POST['mode'] ?? "classic";

    foreach([$page, $per_page, $offset] as $item) {
        if (preg_match("#[^0-9]#", $item)) {
            echo json_encode($mode === "classic" ? [] : _EMPTY_DATA_);
            exit;
        }
    }

    $page = intval($page);
    $per_page = intval($per_page);
    $offset = intval($offset);
    $sql_offset = ($per_page * ($page - 1)) + $offset;

    global $wpdb;
    $result = $wpdb->get_results("select ID, post_name, post_title, post_excerpt, post_date from $wpdb->posts where post_type='post' and post_status='publish' order by post_date desc limit $sql_offset, $per_page", ARRAY_A);

    $stack = [];
    $count = 0;
    foreach($result as $post) {
        $count++;
        $stack[] = [
            'id' => $post['ID'],
            'slug' => $post['post_name'],
            'title' => $post['post_title'],
            'excerpt' => $post['post_excerpt'],
            'date' => $post['post_date']
        ];
    }

    header("Content-Type: application/json");

    echo json_encode([
        'status' => "OK",
        'content' => $mode === "classic" ? $stack : [
            'total' => $count,
            'list' => $stack
        ]
    ]);

    exit;
}

add_action("wp_ajax_articles", "load_articles");
add_action("wp_ajax_nopriv_articles", "load_articles");

?>