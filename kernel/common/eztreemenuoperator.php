<?php
//
// Definition of eZTreeMenuOperator class
//
// Created on: <12-Feb-2003 09:17:07 bf>
//
// Copyright (C) 1999-2006 eZ systems as. All rights reserved.
//
// This source file is part of the eZ publish (tm) Open Source Content
// Management System.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 as published by the Free
// Software Foundation and appearing in the file LICENSE included in
// the packaging of this file.
//
// Licencees holding a valid "eZ publish professional licence" version 2
// may use this file in accordance with the "eZ publish professional licence"
// version 2 Agreement provided with the Software.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "eZ publish professional licence" version 2 is available at
// http://ez.no/ez_publish/licences/professional/ and in the file
// PROFESSIONAL_LICENCE included in the packaging of this file.
// For pricing of this licence please contact us via e-mail to licence@ez.no.
// Further contact information is available at http://ez.no/company/contact/.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.
//
// Contact licence@ez.no if any conditions of this licencing isn't clear to
// you.
//

class eZTreeMenuOperator
{
    /*!
     */
    function eZTreeMenuOperator( $name = 'treemenu' )
    {
        $this->Operators = array( $name );
    }

    /*!
     Returns the operators in this class.
    */
    function &operatorList()
    {
        return $this->Operators;
    }

    /*!
     See eZTemplateOperator::namedParameterList()
    */
    function namedParameterList()
    {
        return array( 'path' => array( 'type' => 'array',
                                       'required' => true,
                                       'default' => false ),
                      'node_id' => array( 'type' => 'int',
                                          'required' => true,
                                          'default' => false ),
                      'class_filter' => array( 'type' => 'array',
                                               'required' => false,
                                               'default' => false ),
                      'depth_skip' => array( 'type' => 'int',
                                             'required' => false,
                                             'default' => false ),
                      'max_level' => array( 'type' => 'int',
                                            'required' => false,
                                            'default' => false ) );
    }

    /*!
     \reimp
    */
    function modify( &$tpl, &$operatorName, &$operatorParameters, &$rootNamespace, &$currentNamespace, &$operatorValue, &$namedParameters )
    {
        $level = 0;
        $done = false;
        $i = 0;
        $pathArray = array();
        $tmpModulePath = $namedParameters['path'];
        $classFilter = $namedParameters['class_filter'];

        if( $classFilter == 'false' )
        {
            $classFilter = array();
        }
        else if ( count( $classFilter ) == 0 )
        {
            $classFilter = array( 1 );
        }
        if ( !$tmpModulePath[count($tmpModulePath)-1]['url'] and isset( $tmpModulePath[count($tmpModulePath)-1]['node_id'] ) )
            $tmpModulePath[count($tmpModulePath)-1]['url'] = "/content/view/full/" . $namedParameters['node_id'];

        $depthSkip = $namedParameters['depth_skip'];

        $maxLevel = $namedParameters['max_level'];
        if ( $maxLevel === false )
            $maxLevel = 2;

        while ( !$done && isset( $tmpModulePath[$i+$depthSkip] ) )
        {
            // get node id
            $elements = explode( "/", $tmpModulePath[$i+$depthSkip]['url'] );
            $nodeID = false;
            if ( isset( $elements[4] ) )
                $nodeID = $elements[4];

            $excludeNode = false;

            if ( isset( $elements[1] ) &&
                 isset( $elements[2] ) &&
                $elements[1] == 'content' &&
                $elements[2] == 'view' &&
                is_numeric( $nodeID ) &&
                $excludeNode == false &&
                $level < $maxLevel )
            {
                $node = eZContentObjectTreeNode::fetch( $nodeID );
                if ( !isset( $node ) ) { $operatorValue = $pathArray; return; }
                if ( isset( $tmpModulePath[$i+$depthSkip+1] ) )
                {
                    $nextElements = explode( "/", $tmpModulePath[$i+$depthSkip+1]['url'] );
                    if ( isset( $nextElements[4] ) )
                    {
                        $nextNodeID = $nextElements[4];
                    }
                    else
                    {
                        $nextNodeID = false;
                    }
                }
                else
                    $nextNodeID = false;

                $menuChildren =& eZContentObjectTreeNode::subTree( array( 'Depth' => 1,
                                                                          'Offset' => 0,
                                                                          'SortBy' => $node->sortArray(),
                                                                          'ClassFilterType' => 'include',
                                                                          'ClassFilterArray' => $classFilter
                                                                          ),
                                                                   $nodeID );

                /// Fill objects with attributes, speed boost
                eZContentObject::fillNodeListAttributes( $menuChildren );

                $tmpPathArray = array();
                foreach ( $menuChildren as $child )
                {
                    $name = $child->attribute( 'name' );
                    $tmpNodeID = $child->attribute( 'node_id' );

                    $url = "/content/view/full/$tmpNodeID/";
                    $urlAlias = "/" . $child->attribute( 'url_alias' );

					$isSelected = false;
					if ( $nextNodeID === $tmpNodeID )
					{
						$isSelected = true;
  					}

                    $tmpPathArray[] = array( 'id' => $tmpNodeID,
                                             'level' => $i,
                                             'url_alias' => $urlAlias,
                                             'url' => $url,
                                             'text' => $name,
											 'is_selected' => $isSelected );
                }

                // find insert pos
                $j = 0;
                $insertPos = 0;
                foreach ( $pathArray as $path )
                {
                    if ( $path['id'] == $nodeID )
                        $insertPos = $j;
                    $j++;
                }
                $restArray = array_splice( $pathArray, $insertPos + 1 );

                $pathArray = array_merge( $pathArray, $tmpPathArray );
                $pathArray = array_merge( $pathArray, $restArray );
            }
            else
            {
                if ( $level == 0 )
                {
                    $node = eZContentObjectTreeNode::fetch( 2 );
                    if ( !isset( $node ) ) { $operatorValue = $pathArray; return; }
                    $menuChildren =& eZContentObjectTreeNode::subTree( array( 'Depth' => 1,
                                                                              'Offset' => 0,
                                                                              'SortBy' => $node->sortArray(),
                                                                              'ClassFilterType' => 'include',
                                                                              'ClassFilterArray' => $classFilter
                                                                              ),
                                                                       2 );

                    /// Fill objects with attributes, speed boost
                    eZContentObject::fillNodeListAttributes( $menuChildren );

                    $pathArray = array();
                    foreach ( $menuChildren as $child )
                    {
                        $name = $child->attribute( 'name' );
                        $tmpNodeID = $child->attribute( 'node_id' );

                        $url = "/content/view/full/$tmpNodeID/";
                        $urlAlias = "/" . $child->attribute( 'url_alias' );

                        $pathArray[] = array( 'id' => $tmpNodeID,
                                              'level' => $i,
                                              'url_alias' => $urlAlias,
                                              'url' => $url,
                                              'text' => $name,
                                              'is_selected' => false );
                    }
                }
                $done = true;
            }
            ++$level;
            ++$i;
        }

        $operatorValue = $pathArray;
    }

    /// \privatesection
    var $Operators;
};

?>
