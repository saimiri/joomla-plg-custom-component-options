<?php
/**
 * A Joomla! plugin for adding custom options to (theoretically) any component.
 *
 * Copyright 2016 Saimiri Design.
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
 * @copyright		Copyright (c) 2016 Saimiri Design (http://www.github.com/saimiri)
 * @author			Juha Auvinen <juha@saimiri.fi>
 * @license			http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link				http://www.github.com/saimiri
 * @since				File available since Release 1.0.0
 */
defined( '_JEXEC' ) or die( 'already' );

class plgContentSmrCustomComponentOptions extends JPlugin
{
	/**
	 * Adds generated XML form files to the current form.
	 *
	 * At the moment only com_menus.item and com_content.article are supported.
	 *
	 * @param type $form
	 * @param type $data
	 * @return boolean
	 */
	public function onContentPrepareForm( $form, $data ) {
		if ( $form->getName() === 'com_menus.item' ) {
			JForm::addFormPath( __DIR__ . '/forms' );
			$form->loadFile( 'com_menus_options', false );
		} elseif ( $form->getName() === 'com_content.article' ) {
			JForm::addFormPath( __DIR__ . '/forms' );
			$form->loadFile( 'com_content_options', false );
		}
		return true;
	}

	/**
	 * Runs after the extension is successfully saved.
	 *
	 * Generates XML form files as defined in the plugin's settings.
	 *
	 * @param type $context
	 * @param type $table
	 * @param type $isNew
	 */
	public function onExtensionAfterSave( $context, $table, $isNew ) {
		if ( $context === 'com_plugins.plugin' &&  $table->name === 'plg_content_smr_custom_component_options' ) {
			$params = json_decode( $table->params );

			$componentFields = $this->parseComponentFields( json_decode( $params->field_list ) );

			foreach ( $componentFields as $component => $options ) {
				$file = __DIR__ . '/forms/' . $component . '_options.xml';
				$this->generateFormXml( $params->fieldset_title, $options, $file );
			}
			$this->removeOldXmlFiles( $componentFields );
		}
	}

	/**
	 * Placeholder for future features.
	 *
	 * @param type $context
	 * @param type $table
	 * @param type $isNew
	 */
	public function onExtensionBeforeSave( $context, $table, $isNew ) {

	}

	/*¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*\
	               ~ PRIVATE AND PROTECTED METHODS AND PROPERTIES ~
	\*__________________________________________________________________________*/

	/**
	 * Load translations automatically.
	 *
	 * @var boolean
	 */
	protected $autoLoadLanguage = true;

	/**
	 *
	 * @param   string   $fieldSetTitle  Title of the fieldset in the form
	 * @param   array    $options        Options to be injected
	 * @param   string   $file           Filename of the XML form file
	 * @return  boolean
	 */
	protected function generateFormXml( $fieldSetTitle, array $options, $file ) {
		// Stop the DOM from bitching about broken HTML. Like we cared.
		set_error_handler( function(){} );

		$doc = new DOMDocument( '1.0', 'utf-8' );
		$doc->formatOutput = true;
		$root = $doc->createElement( 'form' );
		$doc->appendChild( $root );
		$fields = $doc->createElement( 'fields' );
		$fields->setAttribute( 'name', 'params' );
		$root->appendChild( $fields );
		$fieldSet = $doc->createElement( 'fieldset' );
		$fieldSet->setAttribute( 'name', 'smr_custom_component_options' );
		$fieldSet->setAttribute( 'label', $fieldSetTitle );
		$fields->appendChild( $fieldSet );

		$this->appendFields( $doc, $fieldSet, $options );
		$doc->save( $file );

		restore_error_handler();
		return true;
	}

	/**
	 * Adds fields to the XML document as defined by the plugin settings.
	 *
	 * @param  \DOMDocument  $doc       Document used for element creation
	 * @param  \DOMNode      $fieldSet  Fieldset which will contain the fields
	 * @param  array         $fields    A list of field settings
	 */
	protected function appendFields( \DOMDocument $doc, \DOMNode $fieldSet, array $fields ) {
		foreach ( $fields as $field ) {
			//$method = 'create' . ucfirst( $fields->field_types[$i] ) . 'Field';
			switch( $field['type'] ) {
				case 'radio':
				case 'list':
					$method = 'createListField';
					break;
				default:
					$method = 'createGenericField';
			}
			$fieldSet->appendChild( $this->$method(
				$doc,
				$field['type'],
				$field['name'],
				$field['label'],
				$field['options'],
				$field['default_value']
			) );
		}
	}


	/**
	 * Creates a generic field element for the XML.
	 *
	 * @param   \DOMDocument  $doc           Document used for element creation
	 * @param   string        $type          Type of the field
	 * @param   string        $name          Name of the field
	 * @param   string        $label         Field label
	 * @param   array         $options       Options for list elements (not used)
	 * @param   string        $defaultValue  Default value for this field
	 * @return  \DOMElement                  Field element
	 */
	protected function createGenericField(
		\DOMDocument $doc,
		$type,
		$name,
		$label,
		$options,
		$defaultValue
		) {
		$field = $doc->createElement( 'field' );
		$field->setAttribute( 'type', $type );
		$field->setAttribute( 'name', $name );
		$field->setAttribute( 'label', $label );
		$field->setAttribute( 'default', $defaultValue );
		return $field;
	}


	/**
	 * Creates a list element for the XML.
	 *
	 * @param   \DOMDocument  $doc           Document used for element creation
	 * @param   string        $type          Type of the field
	 * @param   string        $name          Name of the field
	 * @param   string        $label         Field label
	 * @param   array         $options       List options
	 * @param   string        $defaultValue  Default value for this field
	 * @return  \DOMElement                  Field element
	 */
	protected function createListField(
		\DOMDocument $doc,
		$type,
		$name,
		$label,
		$options,
		$defaultValue
		) {
		$field = $doc->createElement( 'field' );
		$field->setAttribute( 'type', $type );
		$field->setAttribute( 'name', $name );
		$field->setAttribute( 'label', $label );
		$field->setAttribute( 'default', $defaultValue );

		foreach ( $this->textToOptions( $options ) as $value => $label ) {
			$option = $doc->createElement( 'option' );
			$option->setAttribute( 'value', $value );
			$option->appendChild( $doc->createTextNode( $label ) );
			$field->appendChild( $option );
		}
		return $field;
	}

	/**
	 * Checks if given string is a valid name for a component.
	 *
	 * @param   string  $name  Name of the component
	 * @return  bool
	 */
	protected function isValidComponentName( $name ) {
		return preg_match( '/[a-z_]+/', $name );
	}

	/**
	 * Parses the JSON formatted parameters and builds a more sensible array out
	 * of them.
	 *
	 * @param   array  $fields
	 * @return  array
	 */
	protected function parseComponentFields( $fields ) {
		$componentOptions = [];
		for ( $i = 0, $j = count( $fields->field_names ); $i < $j; $i++ ) {
			foreach ( $fields->field_components[$i] as $component ) {
				if ( !$this->isValidComponentName( $component ) ) {
					continue;
				}
				if ( !isset( $componentOptions[$component] ) ) {
					$componentOptions[$component] = [];
				}
				$componentOptions[$component][] = [
					'name'          => $fields->field_names[$i],
					'label'         => $fields->field_labels[$i],
					'type'          => $fields->field_types[$i],
					'options'       => $fields->field_options[$i],
					'default_value' => $fields->field_default_values[$i]
				];
			}
		}
		return $componentOptions;
	}

	/**
	 * Deletes XML files for those components which have no longer any options.
	 *
	 * @param  array  $componentOptions  A list of components and their options,
	 *                                   generated previously in the code
	 */
	protected function removeOldXmlFiles( array $componentOptions ) {
		foreach ( new FilesystemIterator( __DIR__ . '/forms' ) as $file ) {
			if ( $file->getExtension() === 'xml' ) {
				$component = str_replace( '_options.xml', '', $file->getFilename() );
				if ( !isset( $componentOptions[$component] ) ) {
					unlink( $file->getPathName() );
				}
			}
		}
	}

	/**
	 * Takes a newline separated key=value list and turns it into an associative
	 * array.
	 *
	 * @param   string  $text
	 * @return  array
	 */
	protected function textToOptions( $text ) {
		$options = [];
		foreach ( explode( "\n", $text ) as $line ) {
			list( $key, $value ) = array_map( 'trim', explode( '=', $line ) );
			$options[$key] = $value;
		}
		return $options;
	}
}
