<?php
namespace PHPSocketIO\SessionStorage;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;


/**
 * Description of AbstractStorage
 *
 * @author ricky
 */
abstract class AbstractStorage implements Session\Storage\SessionStorageInterface
{
    protected $id;

    protected $name;

    /**
     * @var MetadataBag
     */
    protected $metadataBag;

    /**
     * @var array
     */
    protected $bags = [];


    public function setMetadataBag(MetadataBag $metaBag = null)
    {
        $this->metadataBag = $metaBag;
    }

    public function clear()
    {
        if($this->metadataBag){
            $this->metadataBag->clear();
        }

        foreach ($this->bags as $bag)
        {
            $bag->clear();
        }

    }

    public function getBag($name)
    {
        if (!isset($this->bags[$name])) {
            throw new \InvalidArgumentException(sprintf('The SessionBagInterface %s is not registered.', $name));
        }

        if (!$this->started) {
            $this->start();
        }

        return $this->bags[$name];
    }

    public function getId()
    {
        return $this->id;
    }

    public function getMetadataBag()
    {
        return $this->metadataBag;
    }

    public function getName()
    {
        return $this->name;
    }

    public function isStarted()
    {
        return $this->started;
    }

    public function registerBag(Session\SessionBagInterface $bag)
    {
        $this->bags[$bag->getName()] = $bag;
    }

    public function setId($id)
    {
        if ($this->started) {
            throw new \LogicException('Cannot set session ID after the session has started.');
        }

        $this->id = $id;
    }


    public function setName($name)
    {
        $this->name = $name;
    }

    protected function generateId()
    {
        return str_replace(array('=', '/', '+'),array('', ',', '.'),base64_encode(sha1(uniqid(mt_rand(). microtime()), true)));
    }

}
