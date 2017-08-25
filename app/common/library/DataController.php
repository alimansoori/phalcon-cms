<?php

namespace Cms;

use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Dispatcher\Exception ;
use Phalcon\Paginator\Adapter\Model as PaginatorModel;
use Phalcon\Validation\Validator;

class DataController extends Controller
{
    /**
     * Modelname Cms\Models\xxxx (full path namespace)
     * @var [type]
     */
    public $model;

    /**
     * Name of primary key
     * @var [type]
     */
    public $id;

    /**
     * Colums for listing
     * @var [type]
     */
    public $list;

    /**
     * Columns for matching to search keyword
     * @var array
     */
    public $search;

    /**
     * Default Form class
     * @var Phalcon\Forms\Form
     */
    public $form;

    /**
     * Default Validation class
     * @var Phalcon\Phalcon\Validation
     */
    public $validation;

    /**
     * Default property on New data
     * @var [type]
     */
    public $default = [

    ];
    /**
     * Default list limit
     * @var integer
     */
    public $limit = 2;

    public function initialize()
    {
        if (!class_exists($this->model)) {
            echo 'Cms\DataController needs model_class property';
        }
        if (empty($this->id)) {
            echo 'Cms\DataController needs id_name property';
            exit;
        }
    }
    public function indexAction()
    {
        $this->tag->setTitleSeparator(' | ');
        $this->tag->setTitle('いんでくす');
        $this->tag->prependTitle('ほげ');
        try {
            $query = $this->model::query();

            // colum list
            if (!empty($this->list)) {
                $query->columns($this->list);
            }

            // filter by colums query parameters
            foreach (get_class_vars($this->model) as $key=> $val) {
                if ($this->request->hasQuery($key)) {
                    $query = $query->andWhere($key .'= :val:', ['val' => $this->request->getQuery($key)]);
                }
            }

            // search by keyword
            if ($this->request->hasQuery('search')) {
                // split by , " ", \r, \t, \n , \f
                $keywords = preg_split("/[\s|\x{3000}]+/u", $this->request->getQuery('search'));
                if (count($keywords)) {
                    // 指定されたカラムを like 検索
                    if (count($this->search)) {
                        $bind = array();
                        foreach ($keywords as $i=>$word) {
                            if (empty($word)) {
                                continue;
                            }
                            $w = array();
                            foreach ($this->search as $col) {
                                $w[] = $col.' like :keyword'.$i.':';
                                $bind['keyword'.$i] = '%'.$word.'%';
                            }
                            // or で結合
                            $word_where[] = '('.implode(' or ', $w).')';
                        }
                    }
                    // and で絞込
                    $where = implode(' and ', $word_where);
                    $query = $query->andWhere($where, $bind);
                }
            }

            // order
            if ($this->request->hasQuery('order')) {
                $desc = '';
                $desc_flg = false;
                if ($this->request->hasQuery('desc') && $this->request->getQuery('desc')) {
                    $desc = ' desc';
                    $desc_flg = true;
                }
                $query->orderBy($this->request->getQuery('order').$desc);
                $this->view->order = (object)[ 'name' => $this->request->getQuery('order'), 'desc' => $desc_flg ];
            } else {
                $query->orderBy($this->id.' desc');
                $this->view->order = (object)[ 'name' => $this->id, 'desc' => true];
            }

            // limit
            $this->view->page  = $this->getPaginate($query->execute(), $this->request->getQuery('limit', 'int', $this->limit));

            $this->view->columns = $this->list;
        } catch (\Exception $e) {
            echo '<pre>'.$e->getTraceAsString().'</pre>';
        }
    }

    public function newAction()
    {
        $form = new $this->form();
        $form->setValidation(new $this->validation());

        if ($this->request->isPost()) {
            $this->save($form);
        }

        $this->view->form = $form;
    }

    public function doneAction()
    {
    }
    public function save(&$form)
    {
        $data = array_merge($this->default, $this->request->getPost());
        if ($form->isValid($data)) {
            $model = new $this->model();
            $model->assign($data);
            try {
                if ($model->save()) {
                    $this->flash->success('Save success');
                    return $this->dispatcher->forward([
                        C=>$this->dispatcher->getControllerName(),
                        A=>'done',
                    ]);
                }
                $form->setModelMessages($model->getMessages());
            } catch (\Exception $e) {
                var_dump($e);
                exit;
            }
        }
        $this->flash->error($this->t->_('You have error'));

        return false;
    }

    /**
    * ページネーション
    * 結果リスト $resultset を $limit 件数毎にページ化しビューにアサインする
    * @method paginate
    * @param  Phalcon\Mvc\Model\ResultSet   $resultset 検索結果リスト
    * @param  int   $limit     １ページあたりの件数
    * @return page              $paginator->getPagenate() 結果
    */
    protected function getPaginate($resultset, $limit)
    {
        $paginator = new PaginatorModel(
            [
                "data"  => $resultset,
                "limit" => $limit,
                "page"  => $this->request->getQuery('page', 'int'),
            ]
        );

        // Get the paginated results
        return $paginator->getPaginate();
    }
}
