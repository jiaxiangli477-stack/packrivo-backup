<?php
/** Isolated importer integration test using transaction-aware WP stubs. */
define('ABSPATH', __DIR__.'/../');
define('PKQ_DIR', __DIR__.'/../packrivo-wp/packrivo-quote/');
define('ARRAY_A', 'ARRAY_A'); define('OBJECT', 'OBJECT'); define('WP_CLI', true);
class WP_Error {
    private $message;
    public function __construct($code,$message) {$this->message=$message;}
    public function get_error_message(){return $this->message;}
}
function is_wp_error($v){return $v instanceof WP_Error;}
class WP_CLI {
    public static $last='';
    public static function add_command($name,$handler){}
    public static function error($message){throw new RuntimeException($message);}
    public static function success($message){self::$last=$message;}
}
function wp_json_encode($data,$flags=0){return json_encode($data,$flags);}
function clean_post_cache($id){} function wp_cache_delete($id,$group){}
$posts=[
 'product-demo-one'=>(object)['ID'=>11,'post_name'=>'product-demo-one','post_type'=>'page','post_status'=>'publish'],
 'product-demo-two'=>(object)['ID'=>22,'post_name'=>'product-demo-two','post_type'=>'page','post_status'=>'publish'],
];
function get_page_by_path($slug,$output,$type){global $posts;return $posts[$slug]??null;}
class FakeDB {
    public $posts='wp_posts',$postmeta='wp_postmeta',$rows=[],$fail_id=0,$started=0,$snapshot=null;
    function prepare($query,...$values){return [$query,$values];}
    function esc_like($s){return $s;}
    function get_results($query,$output){[$sql,$v]=$query;if(strpos($sql,'FROM wp_posts WHERE post_name=')!==false){global $posts;$matches=[];foreach($posts as $p)if($p->post_name===$v[0] && $p->post_type===$v[1] && $p->post_status===$v[2])$matches[]=['ID'=>(string)$p->ID,'post_name'=>$p->post_name,'post_type'=>$p->post_type,'post_status'=>$p->post_status];return $matches;}$id=$v[0];return isset($this->rows[$id]) ? [['meta_id'=>(string)$id,'meta_value'=>$this->rows[$id]]] : [];}
    function get_row($query,$output){[$sql,$v]=$query;if(strpos($sql,'SHOW TABLE STATUS')===0)return ['Engine'=>'InnoDB'];global $posts;foreach($posts as $post)if($post->ID===$v[0])return ['post_name'=>$post->post_name,'post_type'=>$post->post_type,'post_status'=>$post->post_status];return null;}
    function query($sql){if($sql==='START TRANSACTION'){$this->snapshot=$this->rows;$this->started++;}elseif($sql==='ROLLBACK'){$this->rows=$this->snapshot;$this->snapshot=null;}elseif($sql==='COMMIT'){$this->snapshot=null;}return 1;}
    function update($table,$data,$where){$id=$where['post_id'];if($id===$this->fail_id)return false;$this->rows[$id]=$data['meta_value'];return 1;}
    function insert($table,$data){$id=$data['post_id'];if($id===$this->fail_id)return false;$this->rows[$id]=$data['meta_value'];return 1;}
}
$wpdb=new FakeDB();
require __DIR__.'/../packrivo-wp/packrivo-home-v4/inc/33b-product-data.php';
function test($ok,$message){if(!$ok){fwrite(STDERR,"FAIL $message\n");exit(1);}echo "PASS $message\n";}
function call_import($value){$file='/tmp/pk33b-import-fixture.json';file_put_contents($file,json_encode($value,JSON_UNESCAPED_SLASHES));try{pk33b_cli_import([$file],[]);return null;}catch(RuntimeException $e){return $e->getMessage();}}
$one=['slug'=>'product-demo-one','h1'=>'Box "A" \\ design','engine_style'=>'rigid','engine_material'=>'rigidboard','inserts'=>['film-frame']];
$two=['slug'=>'product-demo-two','h1'=>'Box B','engine_style'=>'rigid','engine_material'=>'rigidboard'];
$duplicate=call_import([$one,$one]);test(strpos($duplicate??'','Duplicate')!==false && $wpdb->started===0 && !$wpdb->rows,'duplicate slug rejects before transaction');
$posts['nested-copy']=(object)['ID'=>33,'post_name'=>'product-demo-one','post_type'=>'page','post_status'=>'publish'];
$ambiguous=call_import($one);test(strpos($ambiguous??'','ambiguous')!==false && $wpdb->started===0 && !$wpdb->rows,'ambiguous published slug rejects before transaction');
unset($posts['nested-copy']);
$bad=$two;$bad['inserts']=['unknown'];$error=call_import([$one,$bad]);test($error!==null && $wpdb->started===0 && !$wpdb->rows,'bad later record causes no partial write');
$wpdb->fail_id=22;$error=call_import([$one,$two]);test(strpos($error??'','rolled back')!==false && !$wpdb->rows,'second write failure rolls back first');
$wpdb->fail_id=0;$error=call_import(['products'=>[$one,$two]]);test($error===null && WP_CLI::$last==='updated=2 unchanged=0','valid batch imports both records');
$stored=json_decode($wpdb->rows[11],true);test($stored['h1']===$one['h1'] && $stored['inserts']===['film-frame'],'JSON special characters preserved');
$error=call_import([$one,$two]);test($error===null && WP_CLI::$last==='updated=0 unchanged=2','second import is idempotent');
$error=call_import($one);test($error===null && WP_CLI::$last==='updated=0 unchanged=1','single object import is idempotent');
echo "TOTAL 8 PASS\n";
