<?php
/**
 * Copyright (c) 2010-2011 Arne Blankerts <arne@blankerts.de>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *   * Redistributions of source code must retain the above copyright notice,
 *     this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright notice,
 *     this list of conditions and the following disclaimer in the documentation
 *     and/or other materials provided with the distribution.
 *
 *   * Neither the name of Arne Blankerts nor the names of contributors
 *     may be used to endorse or promote products derived from this software
 *     without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT  * NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER ORCONTRIBUTORS
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    phpDox
 * @subpackage Tests
 * @author     Bastian Feder <phpdox@bastian-feder.de>
 * @copyright  Arne Blankerts <arne@blankerts.de>, All rights reserved.
 * @license    BSD License
 */

namespace TheSeer\phpDox\Tests\Unit {

    use lapistano\ProxyObject\ProxyObject;
    use TheSeer\phpDox\Tests\phpDox_TestCase;
    use TheSeer\phpDox\ClassBuilder;
    use TheSeer\fDOM\fDOMElement;

    class ClassBuilderTest extends phpDox_TestCase {


        /*********************************************************************/
        /* Fixtures                                                          */
        /*********************************************************************/

        /**
         * Provides as stub of the \TheSeer\fDOM\fDOMElement class.
         *
         * @param array $methods
         * @return \TheSeer\fDOM\fDOMElement
         */
        protected function getFDomElementFixture(array $methods = array()) {
            return $this->getMockBuilder('\\TheSeer\\fDOM\\fDOMElement')
                ->disableOriginalConstructor()
                ->setMethods($methods)
                ->getMock();
        }

        /**
         * Provides a stub of the \TheSeer\phpDox\DocBlock\Parser class.
         *
         * @param array $methods
         * @return \TheSeer\phpDox\DocBlock\Parser
         */
        protected function getParserFixture(array $methods = array()) {
            return $this->getMockBuilder('\\TheSeer\\phpDox\\DocBlock\\Parser')
                ->disableOriginalConstructor()
                ->setMethods($methods)
                ->getMock();
        }

        /**
         * Provides an instance of the \TheSeer\phpDox\classBuilderProxy class
         *
         * @return \TheSeer\phpDox\classBuilderProxy
         */
        protected function getClassBuilderProxyFixture() {

            $node = $this->getFDomElementFixture(array('setAttribute'));
            $parser = $this->getParserFixture();
            $ctx = $this->getFDomElementFixture(array());
            $aliasMap = array();
            $publicOnly = false;
            $encoding = 'ISO-8859-1';

            return new classBuilderProxy($parser, $ctx, $aliasMap, $publicOnly, $encoding);
        }

        /*********************************************************************/
        /* Tests                                                             */
        /*********************************************************************/

        /**
         * @dataProvider addReferenceNodeDataprovider
         * @covers \TheSeer\phpDox\ClassBuilder::addReferenceNode
         */
        public function testAddReferenceNode($nodeName, $inheritance, $classShortName, $classPath) {
            $node = $this->getFDomElementFixture(array('setAttribute'));
            $node
                ->expects($this->once())
                ->method('setAttribute')
                ->with(
                    $this->equalTo($inheritance),
                    $this->equalTo($classShortName)
                );

            $class = new \ReflectionClass($classPath);
            $context = $this->getFDomElementFixture(array('appendElementNS'));
            $context
                ->expects($this->once())
                ->method('appendElementNS')
                ->with(
                    $this->equalTo('http://xml.phpdox.de/src#'),
                    $this->equalTo($nodeName)
                )
                ->will($this->returnValue($node));

            $classBuilder = $this->getClassBuilderProxyFixture();
            $this->assertInstanceOf('\DomElement', $classBuilder->addReferenceNode($class, $context, $nodeName));
        }

        /**
         * @dataProvider addReferenceNodeWithNamspacedClassDataprovider
         * @covers \TheSeer\phpDox\ClassBuilder::addReferenceNode
         */
        public function testAddReferenceNodeWithNamspacedClass($nodeName, $inheritance, $classShortName, $classPath) {
            $node = $this->getFDomElementFixture(array('setAttribute'));
            $node
                ->expects($this->exactly(2))
                ->method('setAttribute')
                ->with(
                    $this->logicalOr(
                        $this->equalTo($inheritance),
                        $this->equalTo('namespace')
                    ),
                    $this->logicalOr(
                        $this->equalTo($classShortName),
                        $this->equalTo('TheSeer\phpDox')
                    )
                );

            $class = new \ReflectionClass($classPath);
            $context = $this->getFDomElementFixture(array('appendElementNS'));
            $context
                ->expects($this->once())
                ->method('appendElementNS')
                ->with(
                    $this->equalTo('http://xml.phpdox.de/src#'),
                    $this->equalTo($nodeName)
                )
                ->will($this->returnValue($node));

            $classBuilder = $this->getClassBuilderProxyFixture();
            $this->assertInstanceOf('\DomElement', $classBuilder->addReferenceNode($class, $context, $nodeName));
        }

        /*********************************************************************/
        /* Dataprovider & callbacks                                          */
        /*********************************************************************/

        public static function addReferenceNodeWithNamspacedClassDataprovider() {
            return array(
                'inheritance in namespace' =>
                    array('extends', 'class', 'ClassBuilder', '\\TheSeer\\phpDox\\ClassBuilder'),
                'implementation in namespace' =>
                    array('implements', 'interface', 'ClassBuilder', '\\TheSeer\\phpDox\\ClassBuilder'),
            );
        }

        public static function addReferenceNodeDataprovider() {
            return array(
                'inheritance' => array('extends', 'class', 'stdClass', '\\stdClass'),
                'implementation' => array('implements', 'interface', 'stdClass', '\\stdClass'),
            );
        }

    }

    class classBuilderProxy extends \TheSeer\phpDox\ClassBuilder {

        public function addReferenceNode(\ReflectionClass $class, fDOMElement $context, $nodeName) {
            return parent::addReferenceNode($class, $context, $nodeName);
        }
    }
}