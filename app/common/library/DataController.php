<?php

namespace Cms;

use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Dispatcher\Exception ;
use Phalcon\Paginator\Adapter\Model as PaginatorModel;
use Phalcon\Validation\Validator;
use Phalcon\Mvc\Model;

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
     * @var array
     */
    public $list;

    /**
     * Translate Column
     * @var array
     */
    public $translation;

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

    /**
     * list filter
     * @var array     [description]
     */
    public $filter = [];

    public function initialize()
    {
        if (!class_exists($this->model)) {
            echo 'Cms\DataController needs model_class property';
        }
        if (empty($this->id)) {
            echo 'Cms\DataController needs id_name property';
            exit;
        }
        $this->view->id = $this->id;
    }
    public function indexAction()
    {
        $all = true;

        $this->tag->prependTitle($this->t->_('action_index'));
        try {
            $query = $this->model::query();

            // colum list
            if (!empty($this->list)) {
                $query->columns($this->list);
            }

            // is trash
            if ($this->request->hasQuery('is_trash') and $this->request->getQuery('is_trash')) {
                $query->andWhere('is_trash = '.IS_TRASH);
                $all = false;
            } else {
                $query->andWhere('is_trash = '.IS_NOT_TRASH);
            }

            // filter by colums query parameters
            foreach (get_class_vars($this->model) as $key=> $val) {
                if ($this->request->hasQuery($key)) {
                    $query = $query->andWhere($key .'= :val:', ['val' => $this->request->getQuery($key)]);
                    $all = false;
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
                    $all = false;
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

            // custom query
            $this->customQuery($query);

            // limit
            $this->view->page  = $this->getPaginate($query->execute(), $this->request->getQuery('limit', 'int', $this->limit));

            $this->view->all = $all;
            $this->view->columns = $this->list;

            $this->view->filter = $this->filter();
        } catch (\Exception $e) {
            echo '<pre>'.$e->getTraceAsString().'</pre>';
        }
    }

    public function filter()
    {
        return null;
    }

    public function detailAction($id)
    {
        $this->view->item = $this->model::findFirst([$this->id.'= :id:', 'bind' => ['id' => $id]]);
    }
    public function newAction()
    {
        $form = new $this->form();
        $form->setValidation(new $this->validation());

        if ($this->request->isPost()) {
            if ($this->create($form)) {
                return $this->dispatcher->forward([
                    C=>$this->dispatcher->getControllerName(),
                    A=>'done',
                ]);
            }
        }

        $this->view->form = $form;
        $this->view->pick($this->dispatcher->getControllerName().'/form');
    }

    public function editAction()
    {
        if ($this->request->isGet()) {
            $params = $this->dispatcher->getParams();
            $id = $params[0];
        }

        if ($this->request->isPost()) {
            $id = $this->request->getPost($this->id);
        }

        if (is_numeric($id)) {
            $origin = $this->model::findFirst($this->id.' = ' .$id);
            $form = new $this->form($origin);
            $form->setValidation(new $this->validation());
            $this->view->form = $form;
            if ($this->request->isPost()) {
                if ($this->update($form, $origin)) {
                    return $this->dispatcher->forward([
                        C=>$this->dispatcher->getControllerName(),
                        A=>'done',
                    ]);
                }
            }
            $this->view->pick($this->dispatcher->getControllerName().'/form');
        } else {
            $this->flash->error('Invalid method');
            return $this->dispatcher->forward([
                C=>$this->dispatcher->getControllerName(),
                A=>'done'
            ]);
        }
    }

    public function trashAction()
    {
        if ($this->request->isPost()) {
            $id = $this->request->getPost($this->id);
            $origin = $this->model::findFirst($this->id.' = ' .$id);
            if ($origin) {
                $origin->is_trash = IS_TRASH;
                if ($origin->save()) {
                    $this->flash->success('Trash success');
                    return $this->dispatcher->forward([
                        C=>$this->dispatcher->getControllerName(),
                        A=>'done',
                    ]);
                }
            }
        }
    }
    public function doneAction()
    {
    }

    public function create(&$form)
    {
        $data = array_merge($this->default, $this->request->getPost());
        if ($form->isValid($data)) {
            $model = new $this->model();
            $model->assign($data);
            try {
                if ($model->save()) {
                    $this->flash->success('Create success');
                    return true;
                } else {
                    $form->setModelMessages($model->getMessages());
                    return false;
                }
            } catch (\Exception $e) {
                var_dump($e);
                exit;
            }
        }
        $this->flash->error($this->t->_('You have error'));
        return false;
    }

    public function update(&$form, &$model)
    {
        $data = $this->request->getPost();
        $model->setDirtyState(Model::DIRTY_STATE_PERSISTENT);
        if ($form->isValid($data, $model)) {
            $model->assign($data);
            try {
                if ($model->save()) {
                    $this->flash->success('Update success');
                    return true;
                } else {
                    $form->setModelMessages($model->getMessages());
                    return false;
                }
            } catch (\Exception $e) {
                var_dump($e);
                exit;
            }
        }
        $this->flash->error($this->t->_('You have error'));
    }

    /**
     * custom query if exists for each controller
     * @method customQuery
     * @param  [type]      $query [description]
     * @return [type]             [description]
     */
    protected function customQuery(&$query)
    {
    }
    /**
    * pagination
    * @method paginate
    * @param  Phalcon\Mvc\Model\ResultSet   $resultset 検索結果リスト
    * @param  int   $limit      rows per page
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
