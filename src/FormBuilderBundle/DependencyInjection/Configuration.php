<?php

namespace FormBuilderBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('form_builder');

        $rootNode
            ->children()
                ->variableNode('form_attributes')->end()
                ->arrayNode('flags')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('use_custom_radio_checkbox')->defaultValue(true)->end()
                    ->end()
                ->end()
                ->arrayNode('area')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('presets')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('nice_name')->isRequired()->end()
                                    ->scalarNode('admin_description')->isRequired()->end()
                                    ->arrayNode('sites')
                                        ->useAttributeAsKey('name')
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('form')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('templates')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('label')->isRequired()->end()
                                    ->scalarNode('value')->isRequired()->end()
                                    ->booleanNode('default')->isRequired()->end()
                                ->end()
                                ->canBeUnset()
                                ->canBeDisabled()
                                ->treatNullLike(['enabled' => false])
                                ->validate()
                                    ->ifTrue(function($v) { return $v['enabled'] === false; })
                                    ->thenUnset()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('field')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('templates')
                                    ->useAttributeAsKey('name')
                                    ->prototype('array')
                                        ->children()
                                            ->scalarNode('label')->isRequired()->end()
                                            ->scalarNode('value')->isRequired()->end()
                                            ->booleanNode('default')->isRequired()->end()
                                        ->end()
                                        ->canBeUnset()
                                        ->canBeDisabled()
                                        ->treatNullLike(['enabled' => false])
                                        ->validate()
                                            ->ifTrue(function($v) { return $v['enabled'] === false; })
                                            ->thenUnset()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('admin')
                    ->children()
                        ->arrayNode('active_elements')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('fields')
                                ->prototype('scalar')->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('inactive_elements')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('fields')
                                ->prototype('scalar')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('backend_base_field_type_groups')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('label')->end()
                            ->scalarNode('icon_class')->end()
                        ->end()
                        ->canBeUnset()
                        ->canBeDisabled()
                        ->treatNullLike(['enabled' => false])
                        ->validate()
                            ->ifTrue(function($v) { return $v['enabled'] === false; })
                            ->thenUnset()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('validation_constraints')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('class')->end()
                            ->scalarNode('label')->end()
                            ->scalarNode('icon_class')->end()
                        ->end()
                        ->canBeUnset()
                        ->canBeDisabled()
                        ->treatNullLike(['enabled' => false])
                        ->validate()
                            ->ifTrue(function($v) { return $v['enabled'] === false; })
                            ->thenUnset()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('backend_base_field_type_config')
                    ->children()
                        ->arrayNode('tabs')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('label')->isRequired()->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('display_groups')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('tab_id')->isRequired()->end()
                                    ->scalarNode('label')->isRequired()->end()
                                    ->booleanNode('collapsed')->defaultFalse()->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('fields')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('display_group_id')
                                        ->isRequired()
                                        ->validate()
                                            ->ifInArray(['display_name', 'type', 'template', 'order', 'options'])
                                            ->thenInvalid('%s is a reserved field type id.')
                                        ->end()
                                    ->end()
                                    ->scalarNode('type')->isRequired()->end()
                                    ->scalarNode('label')->isRequired()->end()
                                    ->scalarNode('options_transformer')->defaultValue(null)->end()
                                    ->variableNode('config')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('types')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('class')->cannotBeEmpty()->end()
                                ->arrayNode('configurations')
                                ->scalarPrototype()->cannotBeEmpty()->end()
                            ->end()
                            ->arrayNode('backend')
                                ->children()
                                    ->scalarNode('form_type_group')->isRequired()->end()
                                    ->scalarNode('label')->isRequired()->end()
                                    ->scalarNode('icon_class')->end()
                                    ->arrayNode('constraints')
                                        ->beforeNormalization()
                                            ->ifTrue(function ($value) {
                                                // legacy
                                                return is_bool($value);
                                            })
                                            ->then(function ($value) {
                                                return $value === true
                                                    ? ['enabled' => ['all']]
                                                    : ['disabled' => ['all']];
                                            })
                                        ->end()
                                        ->validate()
                                            ->ifTrue(function ($value) {
                                                return count($value['enabled']) > 0 && count($value['disabled']) > 0;
                                            })
                                            ->thenInvalid('%s is invalid, only one node can be defined ("enabled" or "disabled").')
                                        ->end()
                                        ->validate()
                                            ->always(function($value) {
                                                if (isset($value['enabled']) && in_array('all', $value['enabled'])) {
                                                    return ['disabled' => []];
                                                } elseif (isset($value['disabled']) && in_array('all', $value['disabled'])) {
                                                    return ['enabled' => []];
                                                } elseif (isset($value['enabled']) && !empty($value['enabled'])) {
                                                    return ['enabled' => $value['enabled']];
                                                } elseif (isset($value['disabled']) && !empty($value['disabled'])) {
                                                    return ['disabled' => $value['disabled']];
                                                }
                                                return $value;
                                            })
                                        ->end()
                                        ->children()
                                            ->arrayNode('enabled')
                                                ->prototype('scalar')->end()
                                            ->end()
                                            ->arrayNode('disabled')
                                                ->prototype('scalar')->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('tabs')
                                        ->useAttributeAsKey('name')
                                        ->prototype('array')
                                            ->children()
                                                ->scalarNode('label')->isRequired()->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('display_groups')
                                        ->useAttributeAsKey('name')
                                        ->prototype('array')
                                            ->children()
                                                ->scalarNode('tab_id')->isRequired()->end()
                                                ->scalarNode('label')->isRequired()->end()
                                                ->booleanNode('collapsed')->defaultFalse()->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('fields')
                                        ->useAttributeAsKey('name')
                                        ->prototype('array')
                                            ->children()
                                                ->scalarNode('display_group_id')
                                                    ->isRequired()
                                                    ->validate()
                                                        ->ifInArray(['display_name', 'type', 'template', 'order', 'options'])
                                                        ->thenInvalid('%s is a reserved field type id.')
                                                    ->end()
                                                ->end()
                                                ->scalarNode('type')->isRequired()->end()
                                                ->scalarNode('label')->isRequired()->end()
                                                ->scalarNode('options_transformer')->defaultValue(null)->end()
                                                ->variableNode('config')->end()
                                            ->end()
                                            ->canBeUnset()
                                            ->canBeDisabled()
                                            ->treatNullLike(['enabled' => false])
                                            ->beforeNormalization()
                                                ->ifNull()
                                                ->then(function ($v) {
                                                    $v = ['display_group_id' => null, 'type' => null, 'label' => null, 'enabled' => false];
                                                    return $v;
                                                })
                                            ->end()
                                            ->validate()
                                                ->ifTrue(function($v) { return $v['enabled'] === false; })
                                                ->then(function($v) { return false;})
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('conditional_logic')
                    ->children()
                        ->arrayNode('action')
                        ->useAttributeAsKey('id')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('class')->defaultValue(null)->end()
                                    ->scalarNode('name')->isRequired()->end()
                                    ->scalarNode('icon')->isRequired()->end()
                                    ->arrayNode('form')
                                        ->useAttributeAsKey('name')
                                        ->prototype('array')
                                            ->validate()
                                                ->ifTrue(function($v) { return !empty($v['conditional']) && empty($v['conditional_identifier']); })
                                                ->thenInvalid('conditional form fields requires a valid conditional_identifier.')
                                            ->end()
                                            ->validate()
                                                ->ifTrue(function($v) { return !empty($v['conditional']) && $v['type'] !== 'conditional_select'; })
                                                ->thenInvalid('conditional form is only allowed for type "conditional_select".')
                                            ->end()
                                            ->children()
                                                ->scalarNode('type')->isRequired()->end()
                                                ->scalarNode('label')->isRequired()->end()
                                                ->variableNode('config')->end()
                                                ->scalarNode('options_transformer')->defaultValue(null)->end()
                                                ->scalarNode('conditional_identifier')
                                                    ->validate()
                                                        ->ifEmpty()
                                                        ->thenUnset()
                                                    ->end()
                                                ->end()
                                                ->arrayNode('conditional')
                                                    ->useAttributeAsKey('name')
                                                    ->prototype('array')
                                                        ->children()
                                                            ->scalarNode('type')->isRequired()->end()
                                                            ->scalarNode('label')->isRequired()->end()
                                                            ->variableNode('config')->end()
                                                            ->scalarNode('options_transformer')->defaultValue(null)->end()
                                                        ->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('condition')
                        ->useAttributeAsKey('id')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('class')->defaultValue(null)->end()
                                    ->scalarNode('name')->isRequired()->end()
                                    ->scalarNode('icon')->isRequired()->end()
                                    ->arrayNode('form')
                                        ->useAttributeAsKey('name')
                                        ->prototype('array')
                                            ->children()
                                                ->scalarNode('type')->isRequired()->end()
                                                ->scalarNode('label')->isRequired()->end()
                                                ->variableNode('config')->end()
                                                ->scalarNode('options_transformer')->defaultValue(null)->end()
                                                ->scalarNode('conditional_identifier')
                                                    ->validate()
                                                        ->ifEmpty()
                                                        ->thenUnset()
                                                    ->end()
                                                ->end()
                                                ->arrayNode('conditional')
                                                    ->useAttributeAsKey('name')
                                                    ->prototype('array')
                                                        ->children()
                                                            ->scalarNode('type')->isRequired()->end()
                                                            ->scalarNode('label')->isRequired()->end()
                                                            ->variableNode('config')->end()
                                                            ->scalarNode('options_transformer')->defaultValue(null)->end()
                                                        ->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}