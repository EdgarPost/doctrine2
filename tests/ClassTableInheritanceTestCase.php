<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.com>.
 */

/**
 * Doctrine_ClassTableInheritance_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_ClassTableInheritance_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareTables()
    { }
    public function prepareData()
    { }

    public function testClassTableInheritanceIsTheDefaultInheritanceType()
    {
        $class = new CTITest();

        $table = $class->getTable();

        $this->assertEqual($table->getOption('joinedParents'), array('CTITestParent2', 'CTITestParent3'));
    }

    public function testExportGeneratesAllInheritedTables()
    {
        $sql = $this->conn->export->exportClassesSql(array('CTITest'));
    
        $this->assertEqual($sql[0], 'CREATE TABLE c_t_i_test_parent4 (id INTEGER, age INTEGER, PRIMARY KEY(id))');
        $this->assertEqual($sql[1], 'CREATE TABLE c_t_i_test_parent3 (id INTEGER, added INTEGER, PRIMARY KEY(id))');
        $this->assertEqual($sql[2], 'CREATE TABLE c_t_i_test_parent2 (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(200), verified INTEGER)');
    
        foreach ($sql as $query) {
            $this->conn->exec($query);
        }
    }

    public function testInheritedPropertiesGetOwnerFlags()
    {
        $class = new CTITest();

        $table = $class->getTable();
        
        $columns = $table->getColumns();

        $this->assertEqual($columns['verified']['owner'], 'CTITestParent2');
        $this->assertEqual($columns['name']['owner'], 'CTITestParent2');
        $this->assertEqual($columns['added']['owner'], 'CTITestParent3');
    }

    public function testNewlyCreatedRecordsHaveInheritedPropertiesInitialized()
    {
    	$profiler = new Doctrine_Connection_Profiler();

    	$this->conn->addListener($profiler);

        $class = new CTITest();

        $this->assertEqual($class->toArray(), array('id' => null,
                                                    'age' => null,
                                                    'name' => null,
                                                    'verified' => null,
                                                    'added' => null));

        $class->age = 13;
        $class->name = 'Jack Daniels';
        $class->verified = true;
        $class->added = time();
        $class->save();
        
        // pop the commit event
        $profiler->pop();
        $this->assertEqual($profiler->pop()->getQuery(), 'INSERT INTO c_t_i_test_parent4 (age, id) VALUES (?, ?)');
        // pop the prepare event
        $profiler->pop();
        $this->assertEqual($profiler->pop()->getQuery(), 'INSERT INTO c_t_i_test_parent3 (added, id) VALUES (?, ?)');
        // pop the prepare event
        $profiler->pop();
        $this->assertEqual($profiler->pop()->getQuery(), 'INSERT INTO c_t_i_test_parent2 (name, verified) VALUES (?, ?)');
    }
}
class CTITestParent1 extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 200);
    }
}
class CTITestParent2 extends CTITestParent1
{
    public function setTableDefinition()
    {
    	parent::setTableDefinition();

        $this->hasColumn('verified', 'boolean', 1);
    }
}
class CTITestParent3 extends CTITestParent2
{
    public function setTableDefinition()
    {
        $this->hasColumn('added', 'integer');
    }
}
class CTITestParent4 extends CTITestParent3
{
    public function setTableDefinition()
    {
        $this->hasColumn('age', 'integer', 4);
    }
}
class CTITest extends CTITestParent4
{

}