<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Symfony\Component\DomCrawler\Form;

/**
 * @outputBuffering enabled
 * @db_isolation
 */
class ControllersTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient(
            array(),
            array_merge(ToolsAPI::generateBasicHeader(), array('HTTP_X-CSRF-Header' => 1))
        );
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->client->generate('oro_business_unit_index'));
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
    }

    /**
     * @return array
     */
    public function testUpdateUsers()
    {
        $this->markTestSkipped("BAP-1820");
        $id  = null;
        $this->client->request(
            'GET',
            $this->client->generate('oro_business_update_unit_user_grid')
        );

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);
        $result = ToolsAPI::jsonToArray($result->getContent());
        $result = reset($result['data']);
        return $result;
    }

    /**
     * @depends testUpdateUsers
     * @param array $user
     */
    public function testCreate($user)
    {
        $crawler = $this->client->request('GET', $this->client->generate('oro_business_unit_create'));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_business_unit_form[name]'] = 'testBU';
        $form['oro_business_unit_form[organization]'] = 1;
        $form['oro_business_unit_form[appendUsers]'] = $user['id'];
        $form['oro_business_unit_form[email]'] = 'test@test.com';
        $form['oro_business_unit_form[phone]'] = '123-123-123';
        $form['oro_business_unit_form[fax]'] = '321-321-321';
        $form['oro_business_unit_form[owner]'] = 1;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
        $this->assertContains("Business Unit saved", $crawler->html());
    }

    /**
     * @depends testCreate
     * @return string
     */
    public function testUpdate()
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_business_unit_index', array('_format' =>'json'))
            . '?business_units[_filter][name][value]=testBU',
            array(
                'business_units[_filter][name][type]' => null,
                'business_units[_pager][_page]' => 1,
                'business_units[_pager][_per_page]' => 10,
                'business_units[_sort_by][name]' => 'ASC'
            )
        );

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);

        $result = ToolsAPI::jsonToArray($result->getContent());
        $result = reset($result['data']);

        $crawler = $this->client->request(
            'GET',
            $this->client->generate('oro_business_unit_update', array('id' => $result['id']))
        );
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_business_unit_form[name]'] = 'testBU_Updated';
        $form['oro_business_unit_form[owner]'] = 1;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
        $this->assertContains("Business Unit saved", $crawler->html());

        //get id
        $this->client->request(
            'GET',
            $this->client->generate('oro_business_unit_index', array('_format' =>'json'))
            . '?business_units[_filter][name][value]=testBU_Updated',
            array(
                'business_units[_filter][name][type]' => null,
                'business_units[_pager][_page]' => 1,
                'business_units[_pager][_per_page]' => 10,
                'business_units[_sort_by][name]' => 'ASC'
            )
        );

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);

        $result = ToolsAPI::jsonToArray($result->getContent());
        $result = reset($result['data']);
        return $result['id'];
    }

    /**
     * @depends testUpdate
     * @param string $id
     */
    public function testView($id)
    {

        $crawler = $this->client->request(
            'GET',
            $this->client->generate('oro_business_unit_view', array('id' => $id))
        );

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
        $this->assertContains("testBU_Updated - Business Units - Users Management - System", $crawler->html());
    }

    /**
     * @depends testUpdate
     * @depends testUpdateUsers
     * @param string $id
     * @param array $user
     */
    public function testViewUsers($id, $user)
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_business_view_unit_user_grid', array('id' => $id))
        );

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);
        $result = ToolsAPI::jsonToArray($result->getContent());
        $result = reset($result['data']);
        $this->assertEquals($user['username'], $result['username']);
    }
}
