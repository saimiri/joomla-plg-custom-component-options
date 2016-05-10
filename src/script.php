<?php
/**
 * The install script for SMR Custom Component Options plugin.
 *
 * Copyright 2016 Juha Auvinen.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright   Copyright (c) 2016 Juha Auvinen (http://www.saimiri.fi/)
 * @author      Juha Auvinen <juha@saimiri.fi>
 * @license     http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link        http://www.saimiri.fi/
 * @since       File available since Release 1.0.0
 */
defined( '_JEXEC' ) or die( 'is cast' );

class PlgcontentsmrcustomcomponentoptionsInstallerScript
{

	/**
	 * Setup the plugin.
	 *
	 * The database entry is modified, because when the plugin is installed, the
	 * params field in the database is set to have the translation string as the
	 * default value. However, Joomla! translates default values defined in the
	 * form XML only if the default value is strictly null. So unless we reset it,
	 * no translation will occur.
	 *
	 * @param  object  $parent
	 */
	public function install( $parent ) {
		$db = JFactory::getDbo();
		$query = $db->getQuery( true );

		$query->update( '#__extensions' );
		$query->set( 'params = ' . $db->quote( '{"fieldset_title":null,"field_list":""}' ) );
		$query->where( 'element = ' . $db->quote( 'smrcustomcomponentoptions' ) );
		$query->limit( 1 );

		$db->setQuery( $query );
		$db->execute();
		echo '<p>Database modified.</p>';
	}

	public function preflight( $type, $parent ) {
		echo '<p>Installing SMR Custom Component Options...</p>';
	}

	public function postflight( $type, $parent ) {
		if ( $type === 'install' ) {
			echo '<p>Done!</p>';
		}
	}
}