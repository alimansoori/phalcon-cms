<?php namespace Cms\Models;

class Users extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     * @Primary
     * @Identity
     * @Column(type="integer", length=20, nullable=false)
     */
    public $user_id;

    /**
     *
     * @var string
     * @Column(type="string", length=100, nullable=false)
     */
    public $user_login;

    /**
     *
     * @var string
     * @Column(type="string", length=200, nullable=true)
     */
    public $user_password;

    /**
     *
     * @var string
     * @Column(type="string", length=200, nullable=true)
     */
    public $user_name;

    /**
     *
     * @var string
     * @Column(type="string", length=200, nullable=true)
     */
    public $user_display_name;

    /**
     *
     * @var string
     * @Column(type="string", length=100, nullable=true)
     */
    public $user_role;

    /**
     *
     * @var integer
     * @Column(type="integer", length=11, nullable=true)
     */
    public $user_status;

    /**
     *
     * @var string
     * @Column(type="string", length=2000, nullable=true)
     */
    public $user_image;

    /**
     *
     * @var integer
     * @Column(type="integer", length=20, nullable=false)
     */
    public $site_id;

    /**
     *
     * @var integer
     * @Column(type="integer", length=20, nullable=false)
     */
    public $company_id;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->keepSnapshots(true);

        // $this->setSchema("phalcon-cms");
        $this->setSource("users");
        $this->hasMany('user_id', 'Articles', 'user_id', ['alias' => 'Articles']);
        $this->hasMany('user_id', 'Sites', 'user_id', ['alias' => 'Sites']);
        $this->belongsTo('company_id', 'Cms\Models\Companies', 'company_id', ['alias' => 'Companies']);
        $this->belongsTo('site_id', 'Cms\Models\Sites', 'site_id', ['alias' => 'Sites']);
    }

    public function beforeCreate()
    {
        // encrypt password on create
        $this->user_password = $this->getDI()->get('security')->hash($this->user_password);
    }
    public function beforeUpdate()
    {
        // encrypt password on update
        if ($this->hasSnapshotData()) {
            if ($this->hasChanged('user_password')) {
                $this->user_password = $this->getDI()->get('security')->hash($this->user_password);
            }
        }
    }


    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return Users[]|Users|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return Users|\Phalcon\Mvc\Model\ResultInterface
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'users';
    }
}
