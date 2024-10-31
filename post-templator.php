<?php
/*
  Plugin Name: Post Templator
  Plugin URI:
  Description: シンプルなテンプレートによる投稿作成機能。
  Version: 1.0.0
  Author: HIROKI KANDA
  Author URI: 
  License: GPLv2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

 //add_action群
add_action( 'init', 'pote_create_post' );
register_activation_hook(__FILE__, 'pote_start_post');
add_action( 'admin_menu', 'pote_add_custom_fields' );
//カスタム投稿へはsave_post_カスタム投稿名でadd_action可能
add_action( 'save_post_template', 'pote_save_template_info' );
add_action( 'save_post_template', 'pote_save_template' );

//テンプレートのカスタム投稿タイプ作成
function pote_create_post() {
    // 投稿画面module設定
    $supports = array(
        'title',
        'editor'
    );

    // 管理画面の表示文言
    $labels = array(
        'menu_name' => 'テンプレート',
        'add_new' => '新規テンプレート登録',
        'add_new_item' => '新規テンプレートを登録',
        'edit_item' => 'テンプレートを編集',
        'view_item' => 'テンプレートを表示',
        'search_items' => 'テンプレートを検索',
        'not_found' => 'テンプレートが見つかりませんでした',
        'not_found_in_trash' => 'ゴミ箱にテンプレートはありません'
    );

    register_post_type(
        'template', // カスタム投稿名
        array(
            'label' => 'テンプレート管理', // メニュー名
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'capability_type' => 'post',
            'rewrite' => true,
            'query_var' => false,
            'exclude_from_search' => false,
            'show_in_rest' => true,
            'rest_base' => 'template',
            'has_archive' => true, // アーカイブ有効
            'menu_position' => 5, // 「投稿」の下に配置
            'supports' => $supports, 
            'labels' => $labels
        )
    );
}

//スタート時の機能紹介投稿の作成
function pote_start_post(){
    $post_content = '「Post Templator」へようこそ。このプラグインはテンプレートを登録することで、そこから下書き投稿を作成できます。
        特定の文言を切り替えて類似した投稿を作成する際にコピペをしなくて良いので便利です。

        ・使用法
        ①このテキストを削除して、テンプレートとしたい文章を書き込みます。
        ②投稿ごとに固有としたい部分はこのテキストエリア内に[text1]と書き込みます。[text]は1～10までの10個の登録が可能です。
        ③「[text1]」は管理画面の右側、あるいは下部にあるテンプレートの登録と紐づいています。テキストボックスに入れた文言が置き換わる形になります。

        <b>例：[text1]のテキストボックスにPostTemplatorと入れた場合、以下の文章は投稿では次のように置換されます。
        [text1]はWordpressのプラグインです→PostTemplatorはWordpressのプラグインです。</b>

        ④それまでのテンプレートを保存したい場合は新規作成のプルダウンを「しない」にして「下書き保存」ボタンをクリックします。
        ⑤設定したテンプレート内容で投稿を作成して問題ない場合は新規作成のプルダウンを「する」にして「下書き保存」ボタンをクリックします。
        ⑥以上で、テンプレートに沿った下書き投稿が出来上がります。テンプレートから引き継ぐのは、タイトルと本文です。
    ';

    $post_title = 'Post Templatorへようこそ';
    $post_status = 'draft';
    $post_type = 'template';

    $post = array(
        'post_content'   => $post_content,
        'post_title'     => $post_title,
        'post_status'    => $post_status,
        'post_type'      => $post_type
    );

    $new_template_post = wp_insert_post( $post );
}

//カスタムフィールドの作成
function pote_add_custom_fields() {
    add_meta_box(
        'template_infoid', //id
        'テンプレートの登録',//管理画面の見出し
        'pote_template_info_fields',//  管理画面表示用関数
        'template',//カスタムフィールドを表示する投稿名
        'advanced',// 編集画面セクションが表示される部分
        'default',//優先順位
    );
}

//テンプレートのカスタムフィールド表示HTMLの作成
function pote_template_info_fields() {
    //nonce発行
    wp_nonce_field('wp-nonce-key', 'template_nonce');
    for($i = 1; $i <= 10;$i++): ?>
        <div class="misc-pub-section misc-pub-visibility"><input type="text" id="value<?php echo esc_html($i)?>" name="value<?php echo esc_html($i) ?>" value="<?php echo esc_html(get_post_meta( get_the_ID(), 'value'.$i, true )) ?>" placeholder="[text<?php echo esc_html($i) ?>]の変換対象"></div>
    <?php endfor; ?>
    <div class="misc-pub-section misc-pub-visibility">新規作成</div>
    <div class="misc-pub-section misc-pub-visibility">
        <select id="info_flg" name="info_flg">
            <option value="true">する</option>
            <option value="false" selected>しない</option>
        </select>
    </div>
<?php
}

//テンプレートの保存処理
function pote_save_template_info( $post_id ) {
     //nonce値の確認
     if ( isset($_POST['template_nonce']) && $_POST['template_nonce'] ) {
        if ( check_admin_referer('wp-nonce-key', 'template_nonce') ) {

            // 情報の準備
            $array = array(
                'value1','value2','value3','value4','value5',
                'value6','value7','value8','value9','value10','info_flg'
            );

            foreach ($array as $value) {
                //サニタイズ
                $sanitized_textbox = sanitize_text_field($_POST[$value]);

                //桁数チェック
                if(10 < mb_strlen($sanitized_textbox, 'UTF-8')){
                    $error_message[] = 'ひと言メッセージは10文字以内で入力してください。';
                }

                if( isset( $sanitized_textbox ) ) {
                    if( $sanitized_textbox !== '' ) {
                        update_post_meta( $post_id, $value, $sanitized_textbox );
                    } else {
                        delete_post_meta( $post_id, $value );
                        }
                }
            }
            unset($value); // 最後の要素への参照を解除
        }
     }
}

//投稿に保存
function pote_save_template($post_id) {
    if( !empty($_POST) ){//ゴミ箱移動を除外

        //テキストボックスのデータを取得
        $key_1 = sanitize_text_field(get_post_meta( $post_id, 'value1',true));
        $key_2 = sanitize_text_field(get_post_meta( $post_id, 'value2',true));
        $key_3 = sanitize_text_field(get_post_meta( $post_id, 'value3',true));
        $key_4 = sanitize_text_field(get_post_meta( $post_id, 'value4',true));
        $key_5 = sanitize_text_field(get_post_meta( $post_id, 'value5',true));
        $key_6 = sanitize_text_field(get_post_meta( $post_id, 'value6',true));
        $key_7 = sanitize_text_field(get_post_meta( $post_id, 'value7',true));
        $key_8 = sanitize_text_field(get_post_meta( $post_id, 'value8',true));
        $key_9 = sanitize_text_field(get_post_meta( $post_id, 'value9',true));
        $key_10 = sanitize_text_field(get_post_meta( $post_id, 'value10',true));

        //テンプレートのデータを取得
        $thispost = get_post( $post_id);
        $flg = sanitize_text_field(get_post_meta( $post_id, 'info_flg',true));
        $thispost_title = sanitize_post_field( 'post_title', $thispost->post_title, $thispost->ID, 'display' );
        $thispost_content = sanitize_post_field( 'post_title', $thispost->post_content, $thispost->ID, 'display' );

        //タイトルを置換
        $title_data = array(
            '[text1]' => $key_1,
            '[text2]' => $key_2,
            '[text3]' => $key_3,
            '[text4]' => $key_4,
            '[text5]' => $key_5,
            '[text6]' => $key_6,
            '[text7]' => $key_7,
            '[text8]' => $key_8,
            '[text9]' => $key_9,
            '[text10]' => $key_10,
        );
        
        $target = array_keys($title_data);
        $replace = array_values($title_data);
        
        $set_title = str_replace($target, $replace, $thispost_title);

        //本文を置換
        $content_data = array(
            '[text1]' => $key_1,
            '[text2]' => $key_2,
            '[text3]' => $key_3,
            '[text4]' => $key_4,
            '[text5]' => $key_5,
            '[text6]' => $key_6,
            '[text7]' => $key_7,
            '[text8]' => $key_8,
            '[text9]' => $key_9,
            '[text10]' => $key_10,
        );
        
        $content_target = array_keys($content_data);
        $content_replace = array_values($content_data);
        
        $set_content = str_replace($content_target, $content_replace, $thispost_content);

        //データセット
        $template_post = array(
            'post_title'    => $set_title,
            'post_content'  => $set_content,
            'post_author'   => 1,
        );

        //フラグONなら投稿を作成
        if($flg == 'true'){
            $new_post_id = wp_insert_post( $template_post );
        }
    }
}
?>