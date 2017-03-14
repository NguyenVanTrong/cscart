<?php
// Mock "fn_set_hook()" function
namespace Tygh\Web {

    function fn_set_hook()
    {

    }
}

namespace {
    class SessionTest extends PHPUnit_Framework_TestCase
    {
        public $runTestInSeparateProcess = true;
        public $backupGlobals = false;
        public $preserveGlobalState = false;

        protected function getSessionInstance()
        {
            $application = $this->getMock('\Tygh\Application');
            return  new \Tygh\Web\Session($application);
        }

        /**
         * Tests whether Session object returning variables in a correct way via array access.
         */
        public function testReferencesArrayAccess()
        {
            $session = $this->getSessionInstance();

            $session['foo'] = 10;

            $foo_got_by_value = $session['foo'];
            $foo_got_by_reference = &$session['foo'];

            $session['foo'] = 20;

            $this->assertEquals(10, $foo_got_by_value);
            $this->assertEquals(20, $foo_got_by_reference);
            $this->assertEquals(20, $_SESSION['foo']);
        }

        /**
         * Tests whether Session class correctly implements IteratorAggregate interface.
         */
        public function testIteration()
        {
            $session = $this->getSessionInstance();

            $session_data = array(
                'a' => 1,
                'b' => 2,
                'c' => 3,
                'd' => 4,
                'e' => array(
                    'f' => 5,
                    'g' => 6,
                    'h' => array(
                        'j' => 'k'
                    )
                )
            );

            // Fill session with data
            foreach ($session_data as $key => $value) {
                $session[$key] = $value;
            }

            $session_data_built_by_iterator = array();

            foreach ($session as $key => $value) {
                $session_data_built_by_iterator[$key] = $value;
            }

            $this->assertEquals($session_data, $session_data_built_by_iterator);
            $this->assertEquals($_SESSION, $session_data);
        }

        /**
         * Tests whether Session class correctly implements Countable interface.
         */
        public function testCountable()
        {
            $session = $this->getSessionInstance();

            $this->assertEquals(0, count($session));

            $session_data = array(
                'a' => 1,
                'b' => 2,
                'c' => 3,
                'd' => 4,
                'e' => array(
                    'f' => 5,
                    'g' => 6,
                    'h' => array(
                        'j' => 'k'
                    )
                )
            );

            // Fill session with data
            foreach ($session_data as $key => $value) {
                $session[$key] = $value;
            }

            $this->assertEquals(count($session_data), count($session));
            $this->assertEquals(count($session_data), count($_SESSION));
        }

        /**
         * Tests whether indirect modification of session data works correctly.
         */
        public function testIndirectModification()
        {
            $session = $this->getSessionInstance();

            $session['cart'] = array();

            $session['cart']['products'] = array();

            $this->assertCount(0, $session['cart']['products']);

            $session['cart']['products'][] = 10;
            $session['cart']['products'][] = 20;
            $session['cart']['products'][] = 30;
            $session['cart']['products']['key'] = 'value';
            $session['cart']['products']['foo'] = 'bar';

            $this->assertArrayHasKey('products', $session['cart']);
            $this->assertCount(5, $session['cart']['products']);
        }

        /**
         * Tests whether session component array access works correctly when used at IoC container.
         */
        public function testRegistrationAtContainer()
        {
            $app = new \Tygh\Application();
            $app['session'] = function (\Tygh\Application $app) {
                return new \Tygh\Web\Session($app);
            };

            $app['session']['foo'] = 'bar';

            $this->assertEquals('bar', $app['session']['foo']);

            $this->assertInstanceOf('\Tygh\Web\Session', $app['session']);

            $app['session']['cart'] = array();

            $app['session']['cart']['products'] = array();

            $this->assertCount(0, $app['session']['cart']['products']);

            $app['session']['cart']['products'][] = 10;
            $app['session']['cart']['products'][] = 20;
            $app['session']['cart']['products'][] = 30;
            $app['session']['cart']['products']['key'] = 'value';
            $app['session']['cart']['products']['foo'] = 'bar';

            $this->assertArrayHasKey('products', $app['session']['cart']);
            $this->assertCount(5, $app['session']['cart']['products']);
        }

        public function testSessionName()
        {
            $session = $this->getSessionInstance();

            $this->assertEquals(session_name(), $session->getName());

            $session->setName('foo');

            $this->assertEquals('foo', $session->getName());
            $this->assertEquals('foo', session_name());
        }

        public function testSessionId()
        {
            $session = $this->getSessionInstance();

            $this->assertSessionIsNotStarted();

            $session->setID('bar');
            $this->assertEquals('bar', session_id());

            $session->setID('');
            $this->assertEquals('', session_id());

            $this->assertSessionIsNotStarted();
        }

        protected function assertSessionIsNotStarted()
        {
            if (version_compare(phpversion(), '5.4.0', '>=')) {
                $this->assertFalse(session_status() === PHP_SESSION_ACTIVE);
            }

            $this->assertTrue(session_id() === '');
        }
    }
}