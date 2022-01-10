<?php
/**
 * Date: 2019-09-28 0028
 * Time: 18:39
 */

namespace app\best\controller;


use app\common\model\ArticleModel;
use think\facade\Lang;
use think\facade\Request;

class ArticleController extends LangBaseController
{
    protected $middleware = [
        'InitM' => ['only' => ['index', 'detail']]
    ];
    
    public function index(){
        $article = (new ArticleModel())->LangListArticle();
        //$this->assign('rel_url', (new RelUrlController())->relUrl1('Article'));
        $this->assign('title', Lang::get('article_title'));
        $this->assign('article_heads', $this->generateHeads());
        $this->assign('empty', '<div class="text-center"><img src="/static/web/images/empty.svg"><p class="fw-bold">'.Lang::get('common_no_blog_data').'</p></div>');
        $this->assign('upcomingNumber', (new PhoneController())->getUpcomingNumber());
        return $this->fetch('index', compact('article'));
    }

    public function detail(){
        $id = Request::param('id');
        $article_model = new ArticleModel();
        $article = $article_model->getArticleByIdLang($id);
        //Db::connect('db_master_write')->table('article')->where('id', $article['id'])->setInc('total_num');
        //$this->assign('rel_url', (new RelUrlController())->relUrl1('Article', $id));
        $this->assign('article_detail_heads', $this->generateHeads($article['title']));
        $this->assign('upcomingNumber', (new PhoneController())->getUpcomingNumber());
        return $this->fetch('detail', compact('article'));
    }

    /**
     * 返回头部title description keywords信息
     */
    public function generateHeads($title = ''){
        $heads['title'] = $title . ' ' . Lang::get('index_title');
        $heads['description'] = Lang::get('index_description');
        $heads['keywords'] = Lang::get('index_keywords');
        return $heads;
    }
}