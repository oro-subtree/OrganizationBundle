<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Provider;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\OrganizationBundle\Provider\BusinessUnitAclProvider;

class BusinessUnitAclProviderTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_NAME='test';
    const PERMISSION='VIEW';

    /** @var BusinessUnitAclProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $businessUnitManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclVoter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $treeProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $observer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $user;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $organization;

    protected function setUp()
    {
        $this->businessUnitManager =$this
            ->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclVoter = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->treeProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider')
            ->setMethods(['getTree', 'getUserBusinessUnitIds', 'getUserSubordinateBusinessUnitIds'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->observer = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->will($this->returnValue($this->user));

        $this->securityFacade->expects($this->any())
            ->method('getOrganization')
            ->will($this->returnValue($this->organization));

        $this->provider = new BusinessUnitAclProvider(
            $this->businessUnitManager,
            $this->securityFacade,
            $this->aclVoter,
            $this->treeProvider
        );

        $this->provider->addOneShotIsGrantedObserver($this->observer);
    }

    public function testSystemLevel()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with(self::PERMISSION, 'entity:'.self::ENTITY_NAME)
            ->will($this->returnValue(true));

        $this->observer->expects($this->once())
            ->method('getAccessLevel')
            ->will($this->returnValue(AccessLevel::SYSTEM_LEVEL));

        $this->businessUnitManager->expects($this->exactly(1))
            ->method('getBusinessUnitIds');

        $this->provider->getBusinessUnitIds(self::ENTITY_NAME, self::PERMISSION);
    }

    public function testLocalLevel()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with(self::PERMISSION, 'entity:'.self::ENTITY_NAME)
            ->will($this->returnValue(true));

        $this->observer->expects($this->once())
            ->method('getAccessLevel')
            ->will($this->returnValue(AccessLevel::LOCAL_LEVEL));

        $this->treeProvider->expects($this->exactly(1))
            ->method('getTree')
            ->will($this->returnValue($this->treeProvider));

        $this->treeProvider->expects($this->exactly(1))
            ->method('getUserBusinessUnitIds')
            ->will($this->returnValue($this->treeProvider));

        $this->provider->getBusinessUnitIds(self::ENTITY_NAME, self::PERMISSION);
    }

    public function testDeepLevel()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with(self::PERMISSION, 'entity:'.self::ENTITY_NAME)
            ->will($this->returnValue(true));

        $this->observer->expects($this->once())
            ->method('getAccessLevel')
            ->will($this->returnValue(AccessLevel::DEEP_LEVEL));

        $this->treeProvider->expects($this->exactly(1))
            ->method('getTree')
            ->will($this->returnValue($this->treeProvider));

        $this->treeProvider->expects($this->exactly(1))
            ->method('getUserSubordinateBusinessUnitIds')
            ->will($this->returnValue($this->treeProvider));

        $this->provider->getBusinessUnitIds(self::ENTITY_NAME, self::PERMISSION);
    }

    public function testGlobalLevel()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with(self::PERMISSION, 'entity:'.self::ENTITY_NAME)
            ->will($this->returnValue(true));

        $this->observer->expects($this->once())
            ->method('getAccessLevel')
            ->will($this->returnValue(AccessLevel::GLOBAL_LEVEL));

        $this->businessUnitManager->expects($this->exactly(1))
            ->method('getBusinessUnitIds');

        $this->provider->getBusinessUnitIds(self::ENTITY_NAME, self::PERMISSION);
    }

    public function testAccessNotGranted()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with(self::PERMISSION, 'entity:'.self::ENTITY_NAME)
            ->will($this->returnValue(false));

        $this->businessUnitManager->expects($this->exactly(0))
            ->method('getBusinessUnitIds');

        $this->treeProvider->expects($this->exactly(0))
            ->method('getTree');

        $this->assertEquals([null], $this->provider->getBusinessUnitIds(self::ENTITY_NAME, self::PERMISSION));
    }
}
