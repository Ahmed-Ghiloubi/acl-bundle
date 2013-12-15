<?php

/*
 * This file is part of the ProjectA AclBundle.
 *
 * (c) 1up GmbH
 * (c) Project A Ventures GmbH & Co. KG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProjectA\Bundle\AclBundle\Tests\Model;

use ProjectA\Bundle\AclBundle\Security\Acl\Manager\AceManager\ClassAceManager;
use ProjectA\Bundle\AclBundle\Security\Acl\Manager\AceManager\ObjectAceManager;
use ProjectA\Bundle\AclBundle\Security\Acl\Manager\AclManager;
use Symfony\Component\Security\Acl\Dbal\Schema;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

abstract class AbstractSecurityTest extends WebTestCase
{
    protected $client;
    protected $container;

    /**
     * @var ObjectAceManager
     */
    protected $objectmanager;

    /**
     * @var ClassAceManager
     */
    protected $classmanager;

    /**
     * @var AclManager
     */
    protected $manager;

    protected $object1;
    protected $object2;
    protected $mask1;
    protected $mask2;

    protected function setUp()
    {
        $this->client = static::createClient();
        $this->container = $this->client->getContainer();

        $this->token = $this->createToken();
        $this->container->get('security.context')->setToken($this->token);

        $this->connection = $this->container->get('database_connection');

        if (!class_exists('PDO') || !in_array('sqlite', \PDO::getAvailableDrivers())) {
            $this->markTestSkipped('This test requires SQLite support in your environment.');
        }

        $options = array(
            'oid_table_name' => 'acl_object_identities',
            'oid_ancestors_table_name' => 'acl_object_identity_ancestors',
            'class_table_name' => 'acl_classes',
            'sid_table_name' => 'acl_security_identities',
            'entry_table_name' => 'acl_entries',
        );

        $schema = new Schema($options);

        foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
            $this->connection->exec($sql);
        }

        $this->objectmanager = $this->container->get('projecta_acl.ace.objectmanager');
        $this->classmanager = $this->container->get('projecta_acl.ace.classmanager');
        $this->manager = $this->container->get('projecta_acl.manager');

        $this->object1 = new SomeObject(1);
        $this->object2 = new SomeObject(2);

        $builder1 = new MaskBuilder();
        $builder1
            ->add('view')
            ->add('create')
            ->add('edit')
        ;

        $this->mask1 = $builder1->get();

        $builder2 = new MaskBuilder();
        $builder2
            ->add('delete')
            ->add('undelete')
        ;

        $this->mask2 = $builder2->get();
    }

    protected function getToken()
    {
        return $this->token;
    }

    protected function createToken(array $roles = array())
    {
        $roles += array('ROLE_USER');

        return new UsernamePasswordToken(uniqid(), null, 'main', $roles);
    }

    public function testIfContainerExists()
    {
        $this->assertNotNull($this->client);
        $this->assertNotNull($this->container);
    }

    public function testIfSecurityContextLoads()
    {
        $aclProvider = $this->container->get('security.context');
        $this->assertTrue($aclProvider->isGranted('ROLE_USER'));
        $this->assertFalse($aclProvider->isGranted('ROLE_ADMIN'));
    }
}
