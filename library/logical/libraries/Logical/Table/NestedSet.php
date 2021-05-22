<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Table;

// No direct access
defined('_JEXEC') or die;

use Logical\Registry\Registry;

use InvalidArgumentException;
use RuntimeException;
use ErrorException;
use Exception;

use JText;

/**
 * Class NestedSet
 *
 * @package  Logical\Table
 * @since    0.0.1
 */
abstract class NestedSet extends Table
{
	/**
	 * Cache for the root ID
	 *
	 * @var    integer
	 * @since  3.3
	 */
	protected static $root_id = 0;

	/**
	 * Method to create a row in the database from the JTable instance properties.
	 * If primary key values are set they will be ignored.
	 *
	 * @param   Registry|array  $src     the data to bind before update
	 * @param   array           $ignore  An optional array properties to ignore while binding.
	 *
	 * @return  mixed  Record Key on success or null
	 */
	public function create($src, $ignore = array())
	{
		$this->lockTable();

		$src = $this->normalizeSrc($src);

		$location_id = $src->get('location_id', $src->get('parent_id', $this->getRootId()));
		$location = $src->get('location', 'last-child');

		$position = $this->getPositionData($location_id, $location);

		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);

		$query->update($this->table)
		->set('lft = lft + 2')
		->where($position->get('lftCondition'));
		$this->execute($query);

		$query->clear('set')->clear('where');
		$query->set('rgt = rgt + 2')
		->where($position->get('rgtCondition'));
		$this->execute($query);

		$src = $this->normalizeSrc($src);
		$src->merge($position);

		$result = parent::create($src, $ignore);

		$this->unlockTable();

		return $result;
	}

	/**
	 * Gets the ID of the root item in the tree
	 *
	 * @return  int  The primary id of the root row
	 *
	 * @throws ErrorException
	 */
	protected function getRootId()
	{
		if ((int) self::$root_id > 0)
		{
			return (int) self::$root_id;
		}

		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);

		// First Record is always root
		$primaryKey = $this->getKeyName();
		$query->select($primaryKey)
			->from($this->table)
			->where($primaryKey . ' =  1');
		$dbo->setQuery($query, 0, 1);

		$rootId = $dbo->loadResult();

		if (is_null($rootId))
		{
			throw new ErrorException('LOGICAL_TABLE_ERROR_ROOT_NODE_NOT_FOUND', 500);
		}

		self::$root_id = (int) $rootId;

		return $rootId;
	}

	/**
	 * Method to get various data necessary to make room in the tree at a location
	 * for a node and its children.  The returned data object includes conditions
	 * for SQL WHERE clauses for updating left and right id values to make room for
	 * the node as well as the new left and right ids for the node.
	 *
	 * @param   integer  $location_id  The primary key of the node to reference new location by.
	 * @param   string   $location     Location type string. ['before', 'after', 'first-child', 'last-child']
	 * @param   integer  $nodeWidth    The width of the node for which to make room in the tree.
	 *
	 * @return  Registry on success.
	 */
	protected function getPositionData($location_id, $location = 'last_child', $nodeWidth = 2)
	{
		if ($nodeWidth < 2)
		{
			$this->unlockTable();
			throw new InvalidArgumentException('LOGICAL_TABLE_ERROR_NODE_WIDTH_CANNOT_BE_LESS_THAN_TWO', 400);
		}

		$refNode = $this->getNode($location_id);

		$primaryKey = $this->getKeyName();
		$position = new Registry;

		// We cannot create siblings of the root node
		$siblingLocation = ($location == 'before' || $location == 'after');

		if ($refNode->get($primaryKey) == 1 && $siblingLocation)
		{
			$location = 'last-child';
		}

		// Determine Parent and Level
		$parent_id = $refNode->get('parent_id');
		$level = $refNode->get('level');

		if (strpos($location, 'child') !== false)
		{
			$parent_id = $refNode->get($primaryKey);
			$level++;
		}

		$position->set('parent_id', $parent_id);
		$position->set('level', $level);

		// Determine the base value to use for calculations based on location
		$baseValue = ($location == 'last-child' || $location == 'after') ? $refNode->get('rgt') : $refNode->get('lft');

		// Calculate lft and rgt
		$lft = $baseValue;
		$rgt = $lft + $nodeWidth;

		if ($location == 'first-child' || $location == 'after')
		{
			$lft++;
		}
		elseif ($location == 'last-child' || $location == 'before')
		{
			$rgt--;
		}

		$position->set('lft', $lft);
		$position->set('rgt', $rgt);

		// Set Conditions
		switch ($location)
		{
			case 'before':
				$lftCondition = 'lft >= ' . $baseValue;
				$rgtCondition = 'rgt >= ' . $baseValue;
				break;

			case 'first-child':
			case 'last-child':
				$lftCondition = 'lft > ' . $baseValue;
				$rgtCondition = 'rgt >= ' . $baseValue;
				break;

			case 'after':
			default:
				$lftCondition = 'lft > ' . $baseValue;
				$rgtCondition = 'rgt > ' . $baseValue;
				break;
		}

		$position->set('lftCondition', $lftCondition);
		$position->set('rgtCondition', $rgtCondition);

		return $position;
	}

	/**
	 * Method to get nested set properties for a node in the tree.
	 *
	 * @param   integer  $id   Value to look up the node by.
	 * @param   string   $key  An optional key to look up the node by (parent_id | lft | rgt).
	 *                         If omitted, the primary key of the table is used.
	 *
	 * @return  Registry    Boolean false on failure or node object on success.
	 *
	 * @throws  RuntimeException on database error.
	 */
	protected function getNode($id = 0, $key = null)
	{
		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);
		$primaryKey = $this->getKeyName();

		$query->select(
			array(
				$dbo->qn($primaryKey),
				$dbo->qn('parent_id'),
				$dbo->qn('level'),
				$dbo->qn('lft'),
				$dbo->qn('rgt'),
			)
		);
		$query->from($this->table);

		if (is_null($key))
		{
			$key = $primaryKey;
		}

		if (empty($id))
		{
			$id = $this->getRootId();
		}

		$query->where($dbo->qn($key) . ' = ' . (int) $id);
		$node = $dbo->setQuery($query, 0, 1)->loadObject();

		if (is_null($node))
		{
			$this->unlockTable();
			throw new RuntimeException('LOGICAL_TABLE_ERROR_UNABLE_TO_LOCATE_NODE', 500);
		}

		$node->numChildren = (int) ($node->rgt - $node->lft - 1) / 2;
		$node->width = (int) $node->rgt - $node->lft + 1;

		if ($node->{$key} == 1)
		{
			// The root node is always 0, so we have to change this
			$node->parent_id = 1;
		}

		return new Registry($node);
	}

	/**
	 * Runs a query and unlocks the database on an error.
	 *
	 * @param   mixed  $query  A string or JDatabaseQuery object.
	 *
	 * @throws  Exception on database error.
	 *
	 * @return  boolean  void
	 */
	protected function execute($query)
	{
		// Prepare to catch an exception.
		try
		{
			$dbo = $this->getDbo();
			$dbo->setQuery($query)->execute();
		}
		catch (Exception $e)
		{
			// Unlock the tables and rethrow.
			$this->unlockTable();
			throw $e;
		}
	}

	/**
	 * Method to perform sanity checks on the src data to ensure
	 * they are safe to store in the database. Child classes should chain this
	 * method to make sure the data they are storing in the database is safe and
	 * as expected before storage.
	 *
	 * @param   Registry|array  $src     the data to bind before update
	 * @param   array           $ignore  An optional array properties to ignore while binding.
	 *
	 * @return Registry
	 */
	public function check($src, $ignore = array())
	{
		$src = $this->normalizeSrc($src);

		// Creating or setting the root node value is forbidden.
		$ignore[] = 'root';

		return parent::check($src, $ignore);
	}

	/**
	 * Method to check if the source has a valid parent_id
	 *
	 * @param   Registry  $src  The record data
	 *
	 * @throws ErrorException
	 *
	 * @return void
	 */
	private function checkParent($src)
	{
		$parent_id = $src->get('parent_id', $this->getRootId());

		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);
		$primaryKey = $this->getKeyName();

		$query->select('parent_id')
			->from($this->table)
			->where($primaryKey . ' = ' . (int) $src->get($primaryKey, 0));
		$result = $dbo->setQuery($query)->loadObject();

		if (is_null($result) || $result->parent_id != $parent_id)
		{
			$src->set('location_id', $parent_id);
			$src->set('location', 'last-child');
		}
	}

	/**
	 * Method to update a row in the database from the JTable instance properties.
	 * If primary key values are not set this method will call the create method
	 *
	 * @param   array|object  $src     the data to bind before update
	 * @param   array         $ignore  An optional array properties to ignore while binding.
	 *
	 * @return  boolean  True on success.
	 */
	public function update($src, $ignore = array())
	{
		$src = $this->normalizeSrc($src);
		$primaryKey = $this->getKeyName();

		if ($src->get($primaryKey) == 1)
		{
			throw new InvalidArgumentException('LOGICAL_TABLE_ERROR_CANNOT_UPDATE_ROOT_NODE');
		}

		$this->lockTable();

		$this->checkParent($src);

		if (!empty($src->get('location_id', null)))
		{
			$primaryKey = $this->getKeyName();
			$location_id = $src->get('location_id');
			$location = $src->get('location', 'last-child');

			$position = $this->moveByReference($src->get($primaryKey), $location_id, $location);

			$src->merge($position);
		}

		$result = parent::update($src, $ignore);

		$this->unlockTable();

		return $result;
	}

	/**
	 * Method to move a node and its children to a new location in the tree.
	 *
	 * @param   integer  $pk           The primary key of the node to move.
	 * @param   integer  $location_id  The primary key of the node to reference new location by.
	 * @param   string   $location     Location type string. ['before', 'after', 'first-child', 'last-child']
	 *
	 * @throws  InvalidArgumentException
	 *
	 * @return  Registry  True on success.
	 */
	protected function moveByReference($pk, $location_id, $location = null)
	{
		$node = $this->getNode($pk);
		$primaryKey = $this->getKeyName();

		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);

		// Get the ids of child nodes.
		$query->select($primaryKey)
			->from($this->table)
			->where('lft BETWEEN ' . (int) $node->get('lft') . ' AND ' . (int) $node->get('rgt'));

		$children = $dbo->setQuery($query)->loadColumn();
		$query->clear();

		// Cannot move the node to be a child of itself.
		if (in_array($location_id, $children))
		{
			$this->unlockTable();
			throw new InvalidArgumentException('LOGICAL_TABLE_ERROR_CANNOT_MOVE_PARENT_NODE_TO_CHILD_NODE', 400);
		}

		// Move the sub-tree out of the nested sets by negating its left and right values.
		$query->update($this->table)
			->set('lft = lft * (-1), rgt = rgt * (-1)')
			->where('lft BETWEEN ' . (int) $node->get('lft') . ' AND ' . (int) $node->get('rgt'));
		$this->execute($query);
		$query->clear();

		// Close the hole in the tree that was opened by removing the sub-tree from the nested sets.
		$query->update($this->table)
			->set('lft = lft - ' . (int) $node->get('width'))
			->where('lft > ' . (int) $node->get('rgt'));
		$this->execute($query);
		$query->clear();

		// Compress rgt values
		$query->update($this->table)
			->set('rgt = rgt - ' . (int) $node->get('width'))
			->where('rgt > ' . (int) $node->get('rgt'));
		$this->execute($query);
		$query->clear();

		$position = $this->getPositionData($location_id, $location, $node->get('width'));

		// Create space in the nested sets at the new location for the moved sub-tree.
		$query->update($this->table)
			->set('lft = lft + ' . (int) $node->get('width'))
			->where($position->get('lftCondition'));
		$this->execute($query);
		$query->clear();

		$query->update($this->table)
			->set('rgt = rgt + ' . (int) $node->get('width'))
			->where($position->get('rgtCondition'));
		$this->execute($query);
		$query->clear();

		/*
		 * Calculate the offset between where the node used to be in the tree and
		 * where it needs to be in the tree for left ids (also works for right ids).
		 */
		$offset = $position->get('lft') - $node->get('lft');
		$levelOffset = $position->get('level') - $node->get('level');

		// Move the nodes back into position in the tree using the calculated offsets.
		$query->update($this->table)
			->set('rgt = ' . (int) $offset . ' - rgt')
			->set('lft = ' . (int) $offset . ' - lft')
			->set('level = level + ' . (int) $levelOffset)
			->where('lft < 0');
		$this->execute($query);

		return $position;
	}

	/**
	 * Method to load a row from the database by primary key and bind the fields
	 * to the JTable instance properties.
	 *
	 * @param   int    $pk      The primary key of the record to load,
	 * @param   array  $ignore  An optional array or space separated list of properties to ignore while binding.
	 *
	 * @throws ErrorException if no record is found
	 * @throws InvalidArgumentException if $pk is empty
	 *
	 * @return  object  True if successful. False if row not found.
	 */
	public function load($pk, $ignore = array())
	{
		$record = parent::load($pk, $ignore);
		$record->path = $this->getPath($pk);

		return $record;
	}

	/**
	 * Method to get a node path
	 *
	 * @param   int  $nodeId  the id of the node we are looking for
	 *
	 * @return mixed
	 */
	public function getPath($nodeId)
	{
		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);
		$primaryKey = $this->getKeyName();

		$aliasField = $this->getAliasField();

		if (is_null($aliasField))
		{
			return '';
		}

		$query->select('GROUP_CONCAT(parent.' . $aliasField . ' SEPARATOR \'/\') AS path')
			->from($dbo->qn($this->table, 'node'))
			->join('INNER', $dbo->qn($this->table, 'parent'))
			->where('node.lft BETWEEN parent.lft AND parent.rgt')
			->where('node.' . $primaryKey . ' = ' . (int) $nodeId)
			->order('parent.lft');

		$dbo->setQuery($query);

		return $dbo->loadResult();
	}

	/**
	 * Method to delete a record by primary key
	 *
	 * @param   int   $pk              The primary key of the record to delete,
	 * @param   bool  $deleteChildren  True to delete children nodes, false to move them up a level
	 *
	 * @throws ErrorException
	 * @throws InvalidArgumentException
	 *
	 * @return  boolean  True on success.
	 */
	public function delete($pk, $deleteChildren = true)
	{
		if ($pk == $this->getRootId())
		{
			throw new InvalidArgumentException(JText::_('LOGICAL_TABLE_ERROR_CANNOT_DELETE_ROOT_NODE'));
		}

		$this->lockTable();

		$dbo   = $this->getDbo();
		$query = $dbo->getQuery(true);
		$node = $this->getNode($pk);

		if ($deleteChildren)
		{
			// Delete the node and all of its children.
			$query->delete($this->table)
				->where('lft BETWEEN ' . (int) $node->get('lft') . ' AND ' . (int) $node->get('rgt'));
			$this->execute($query);
			$query->clear();

			// Compress the left values.
			$query->update($this->table)
				->set('lft = lft - ' . (int) $node->get('width'))
				->where('lft > ' . (int) $node->get('rgt'));
			$this->execute($query);
			$query->clear();

			// Compress the right values.
			$query->update($this->table)
				->set('rgt = rgt - ' . (int) $node->get('width'))
				->where('rgt > ' . (int) $node->get('rgt'));
			$this->execute($query);

			$this->unlockTable();

			return true;
		}

		// Or move the children up a level

		// Delete the node.
		$query->delete($this->table)
			->where('lft = ' . (int) $node->get('lft'));
		$this->execute($query);
		$query->clear();

		// Shift all node's children up a level.
		$query->update($this->table)
			->set('lft = lft - 1')
			->set('rgt = rgt - 1')
			->set('level = level - 1')
			->where('lft BETWEEN ' . (int) $node->get('lft') . ' AND ' . (int) $node->get('rgt'));
		$this->execute($query);
		$query->clear();

		// Adjust all the parent values for direct children of the deleted node.
		$primaryKey = $this->getKeyName();

		$query->update($this->table)
			->set('parent_id = ' . (int) $node->get('parent_id'))
			->where('parent_id = ' . (int) $node->get($primaryKey));
		$this->execute($query);
		$query->clear();

		// Shift all of the left values that are right of the node.
		$query->update($this->table)
			->set('lft = lft - 2')
			->where('lft > ' . (int) $node->get('rgt'));
		$this->execute($query);
		$query->clear();

		// Shift all of the right values that are right of the node.
		$query->update($this->table)
			->set('rgt = rgt - 2')
			->where('rgt > ' . (int) $node->get('rgt'));
		$this->execute($query);
		$query->clear();

		$this->unlockTable();

		return true;
	}

	/**
	 * Method to recursively rebuild the whole nested set tree.
	 *
	 * @param   integer  $parentId  The root of the tree to rebuild.
	 * @param   integer  $lft       The left id to start with in building the tree.
	 * @param   integer  $level     The level to assign to the current nodes.
	 * @param   string   $sql       The sql statement to execute via the rebuild
	 *
	 * @return  integer  root rgt value + 1 on success
	 */
	public function rebuild($parentId = null, $lft = 0, $level = 0, $sql = null)
	{
		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);
		$primaryKey = $this->getKeyName();

		if (is_null($parentId))
		{
			$parentId = $this->getRootId();
			$lft = 1;

			// First we need to check for corrupted nodes
			$query->update($this->table)
				->set('parent_id = 1')
				->where('parent_id = 0')
				->where($primaryKey . ' != 1');
			$dbo->setQuery($query)->execute();
			$query->clear();
		}

		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);
		$primaryKey = $this->getKeyName();

		if (is_null($sql))
		{
			$query->clear()
				->select($primaryKey)
				->from($this->table)
				->where('parent_id = %d');
			$query->order('parent_id, lft');
			$sql = (string) $query;
		}

		// Assemble the query to find all children of this node.
		$dbo->setQuery(sprintf($sql, (int) $parentId));
		$children = $dbo->loadObjectList();

		// The right value of this node is the left value + 1
		$rgt = $lft + 1;

		foreach ($children AS $node)
		{
			$rgt = $this->rebuild($node->{$primaryKey}, $rgt, $level + 1, $sql);
		}

		// We've got the left value, and now that we've processed
		// the children of this node we also know the right value.
		$query->clear();
		$query->update($this->table)
			->set('lft = ' . (int) $lft)
			->set('rgt = ' . (int) $rgt)
			->set('level = ' . (int) $level)
			->where($primaryKey . ' = ' . (int) $parentId);
		$dbo->setQuery($query)->execute();

		// Return the right value of this node + 1.
		return $rgt + 1;
	}

	/**
	 * Method to get location information by node id
	 *
	 * @param   int  $pk  the primary key of the node to lookup
	 *
	 * @return object
	 */
	public function getLocation($pk)
	{
		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);
		$primaryKey = $this->getKeyName();

		$query->select(
			array(
				$dbo->qn('first_sibling.' . $primaryKey, 'first_sibling'),
				$dbo->qn('last_sibling.' . $primaryKey, 'last_sibling'),
				$dbo->qn('lft_sibling.' . $primaryKey, 'lft_sibling'),
				$dbo->qn('rgt_sibling.' . $primaryKey, 'rgt_sibling')
			)
		)
			->from($dbo->qn($this->table, 'node'))
			->join('LEFT', $dbo->qn($this->table, 'parent') . ' ON parent.' . $primaryKey . ' = node.parent_id')
			->join(
				'LEFT',
				$dbo->qn($this->table, 'first_sibling') . ' ON first_sibling.lft = parent.lft + 1 AND first_sibling.'
				. $primaryKey . ' != node.' . $primaryKey
			)
			->join(
				'LEFT',
				$dbo->qn($this->table, 'last_sibling') . ' ON last_sibling.rgt = parent.rgt - 1 AND last_sibling.'
				. $primaryKey . ' != node.' . $primaryKey
			)
			->join('LEFT', $dbo->qn($this->table, 'lft_sibling') . ' ON lft_sibling.rgt = node.lft - 1 AND lft_sibling.parent_id = node.parent_id')
			->join('LEFT', $dbo->qn($this->table, 'rgt_sibling') . ' ON rgt_sibling.lft = node.rgt + 1 AND lft_sibling.parent_id = node.parent_id')
			->where('node.' . $primaryKey . ' = ' . (int) $pk);

		return $dbo->setQuery($query)->loadObject();
	}

	/**
	 * Method to truncate the table and restore the root element.
	 *
	 * @return boolean
	 */
	public function truncate()
	{
		if (!parent::truncate())
		{
			return false;
		}

		// Add the root element back
		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);
		$query->insert($this->table)
			->columns(array('nested_title', 'nested_alias', 'parent_id', 'level', 'lft', 'rgt'))
			->values("'ROOT', 'ROOT', 0, 0, 1, 2");
		$dbo->setQuery($query);

		return ($dbo->execute() !== false);
	}
}
