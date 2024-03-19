<?php
/** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnusedParameterInspection */

namespace Walnut\Lang\Implementation\Compilation;

use Walnut\Lang\Blueprint\Compilation\ModuleImporter;
use Walnut\Lang\Blueprint\Expression\Expression;
use Walnut\Lang\Blueprint\Expression\MethodCallExpression;
use Walnut\Lang\Blueprint\Range\MinusInfinity;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Registry\ProgramBuilder;
use Walnut\Lang\Implementation\Compilation\Token as T;
use Walnut\Lib\Walex\Token as LT;

final readonly class ParserStateMachine {
	public function __construct(
		private ParserState $s, 
		private ProgramBuilder $programBuilder,
		private ModuleImporter $moduleImporter,
	) {}

	public function getAllStates(): array {
		return [
			-1 => ['name' => 'EOF', 'transitions' => [
				'' => function(LT $token) {
					$this->s->move(-1);
				},
			]],
			101 => ['name' => 'module start', 'transitions' => [
				T::module_identifier->name => function(LT $token) {
					$moduleId = substr($token->patternMatch->text, 7, -1);
					if (str_contains($moduleId, '%%')) {
						[$moduleName, $dependencyNames] = explode('%%', $moduleId);
						$dependencyNames = array_map('trim', explode(',', $dependencyNames));
					} else {
						$moduleName = $moduleId;
						$dependencyNames = [];
					}
					if ($moduleName !== 'core') {
						$this->moduleImporter->importModule('core');
					}
					foreach($dependencyNames as $dependencyName) {
						$this->moduleImporter->importModule($dependencyName);
					}
					$this->s->move(102);
				}
			]],
			102 => ['name' => 'module content start', 'transitions' => [
				T::var_keyword->name => function(LT $token) {
					$this->s->result = [];
					$this->s->result['variableName'] = $token->patternMatch->text;
					$this->s->move(103);
				},
				T::type_keyword->name => function(LT $token) {
					$this->s->result = [];
					$this->s->result['typeName'] = $token->patternMatch->text;
					$this->s->move(104);
				},
				T::cast_marker->name => function(LT $token) {
					$this->s->result = [];
					$this->s->result['typeName'] = 'DependencyContainer';
					$this->s->move(119);
				},
				'EOF' => -1
			]],
			103 => ['name' => 'module level type definition', 'transitions' => [
				'assign' => function(LT $token) {
					$this->s->push(133);
					$this->s->move(401);
				}
			]],
			104 => ['name' => 'module level type definition', 'transitions' => [
				'assign' => 105,
				'subtype' => 113,
				'cast_marker' => 119,
				'method_marker' => 122,
				'call_start' => 141,
				'tuple_start' => 142,
			]],
			105 => ['name' => 'module level type assignment', 'transitions' => [
				'atom_type' => 106,
				'enum_type_start' => function(LT $token) {
					$this->s->result['enumerationValues'] = [];
					$this->s->move(107);
				},
				//'sequence_start' => 110,
				T::this_var->name => 110,
				'type_keyword' => $c = function(LT $token) {
					$this->s->push(131);
					$this->s->stay(701);
				},
				'lambda_param' => $c,
				'tuple_start' => $c,
				'call_start' => $c,
				'empty_tuple' => 810,
				'empty_record' => 811,
			]],

			106 => ['name' => 'module level atom', 'transitions' => [
				'expression_separator' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeBuilder()['addAtom']($this->s->result['typeName']);
					$this->s->move(102);
				}
			]],
			107 => ['name' => 'module level enum', 'transitions' => [
				'type_keyword' => function(LT $token) {
					$this->s->result['enumerationValues'] ??= [];
					$this->s->result['enumerationValues'][] = $token->patternMatch->text;
					$this->s->move(108);
				},
			]],
			108 => ['name' => 'module level enum separator', 'transitions' => [
				'value_separator' => 107,
				'tuple_end' => 109
			]],
			109 => ['name' => 'module level enum end', 'transitions' => [
				T::expression_separator->name => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeBuilder()['addEnumeration'](
						$this->s->result['typeName'], $this->s->result['enumerationValues'] ?? []);
					$this->s->move(102);
				}
			]],

			110 => ['name' => 'state type type', 'transitions' => [
				T::tuple_start->name => /*$c =*/ function(LT $token) {
					$this->s->push(/*111*/ 112);
					$this->s->stay(701);
				},
				//T::type_keyword->name => $c,
				//T::lambda_param->name => $c,
			]],
			/*111 => ['name' => 'state type type return', 'transitions' => [
				T::sequence_end->name => 112
			]],*/
			112 => ['name' => 'state type return', 'transitions' => [
				T::expression_separator->name => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeBuilder()['addState'](
						$this->s->result['typeName'],
						$this->s->generated
					);
					$this->s->move(102);
				}
			]],
			113 => ['name' => 'subtype base type', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(114);
					$this->s->stay(701);
				}
			]],
			114 => ['name' => 'subtype base type return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['base_type'] = $this->s->generated;
					$this->s->stay(115);
				}
			]],
			115 => ['name' => 'subtype error type', 'transitions' => [
				T::error_marker->name => function(LT $token) {
					$this->s->push(116);
					$this->s->move(701);
				},
				T::function_body_marker->name => function(LT $token) {
					$this->s->push(118);
					$this->s->move(201);
				},
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->expressionRegistry()['constant'](
						$this->programBuilder->valueRegistry()['null']()
					);
					$this->s->stay(118);
				},
			]],
			116 => ['name' => 'subtype error type return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['error_type'] = $this->s->generated;
					$this->s->stay(117);
				}
			]],
			117 => ['name' => 'subtype error type', 'transitions' => [
				T::function_body_marker->name => function(LT $token) {
					$this->s->push(118);
					$this->s->move(201);
				},
			]],
			118 => ['name' => 'subtype result', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeBuilder()['addSubtype'](
						$this->s->result['typeName'],
						$this->s->result['base_type'],
						$this->programBuilder->expressionRegistry()['functionBody']($this->s->generated),
						$this->s->result['error_type'] ?? null,
					);
					$this->s->move(102);
				}
			]],
			119 => ['name' => 'cast base type', 'transitions' => [
				T::type_keyword->name => function(LT $token) {
					$this->s->result['castToTypeName'] = $token->patternMatch->text;
					$this->s->move(120);
				}
			]],
			120 => ['name' => 'cast body marker', 'transitions' => [
				T::error_marker->name => function(LT $token) {
					$this->s->push(129);
					$this->s->move(701);
				},
				T::dependency_marker->name => function(LT $token) {
					$this->s->push(153);
					$this->s->move(701);
				},
				T::function_body_marker->name => function(LT $token) {
					$this->s->push(121);
					$this->s->move(201);
				},
			]],
			121 => ['name' => 'cast body result', 'transitions' => [
				'' => function(LT $token) {
					$errorType = $this->s->result['error_type'] ?? null;
					$returnType = $this->programBuilder->typeRegistry()['TypeByName']($this->s->result['castToTypeName']);
					if ($errorType) {
						$returnType = $this->programBuilder->typeRegistry()['Result']($returnType, $errorType);
					}
					$this->s->generated = $this->programBuilder->methodBuilder()['addMethod'](
						$this->programBuilder->typeRegistry()['TypeByName']($this->s->result['typeName']),
						'as' . $this->s->result['castToTypeName'],
						$this->programBuilder->typeRegistry()['Null'](),
						$this->s->result['dependency_type'] ?? null,
						$returnType,
						$this->programBuilder->expressionRegistry()['functionBody']($this->s->generated),
					);
					$this->s->move(102);
				}
			]],
			122 => ['name' => 'method definition start', 'transitions' => [
				T::var_keyword->name => $c = function(LT $token) {
					$this->s->result['method_name'] = $token->patternMatch->text;
					$this->s->move(123);
				},
				T::type_keyword->name => $c
			]],

			123 => ['name' => 'method name next', 'transitions' => [
				T::call_start->name => 124
			]],
			124 => ['name' => 'method name lambda', 'transitions' => [
				T::lambda_param->name => function(LT $token) {
					$this->s->push(125);
					$this->s->move(901);
				}
			]],
			125 => ['name' => 'method name close param', 'transitions' => [
				T::call_end->name => function(LT $token) {
					$this->s->result['parameter_type'] = $this->s->generated;
					$this->s->move(126);
				}
			]],
			126 => ['name' => 'method name body', 'transitions' => [
				T::dependency_marker->name => function(LT $token) {
					$this->s->push(151);
					$this->s->move(701);
				},
				T::function_body_marker->name => function(LT $token) {
					$this->s->push(127);
					$this->s->move(201);
				},
			]],
			127 => ['name' => 'method name result', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->methodBuilder()['addMethod'](
						$this->programBuilder->typeRegistry()['TypeByName']($this->s->result['typeName']),
						$this->s->result['method_name'],
						$this->s->result['parameter_type']->parameterType(),
						$this->s->result['dependency_type'] ?? null,
						$this->s->result['parameter_type']->returnType(),
						$this->programBuilder->expressionRegistry()['functionBody']($this->s->generated),
					);
					$this->s->move(102);
				}
			]],
			129 => ['name' => 'cast error type return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['error_type'] = $this->s->generated;
					$this->s->stay(130);
				}
			]],
			130 => ['name' => 'cast error type return', 'transitions' => [
				T::dependency_marker->name => function(LT $token) {
					$this->s->push(153);
					$this->s->move(701);
				},
				T::function_body_marker->name => function(LT $token) {
					$this->s->push(121);
					$this->s->move(201);
				},
			]],

			131 => ['name' => 'module level type alias end', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeBuilder()['addAlias']($this->s->result['typeName'], $this->s->generated);
					$this->s->stay(132);
				},
			]],
			132 => ['name' => 'module level separator', 'transitions' => [
				'expression_separator' => 102
			]],

			133 => ['name' => 'variable name end', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->valueBuilder()['addVariable']($this->s->result['variableName'], $this->s->generated);
					$this->s->stay(134);
				},
			]],
			134 => ['name' => 'variable name separator', 'transitions' => [
				'expression_separator' => 102
			]],

			141 => ['name' => 'constructor method parameter', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(143);
					$this->s->stay(701);
				}
			]],
			142 => ['name' => 'constructor method parameter tuple or record', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(146);
					$this->s->back(701);
				}
			]],
			143 => ['name' => 'constructor method parameter return', 'transitions' => [
				T::call_end->name => function(LT $token) {
					$this->s->result['parameter_type'] = $this->s->generated;
					$this->s->move(144);
				}
			]],
			144 => ['name' => 'constructor method body', 'transitions' => [
				T::error_marker->name => function(LT $token) {
					$this->s->push(147);
					$this->s->move(701);
				},
				T::dependency_marker->name => function(LT $token) {
					$this->s->push(152);
					$this->s->move(701);
				},
				T::function_body_marker->name => function(LT $token) {
					$this->s->push(145);
					$this->s->move(201);
				},
			]],
			145 => ['name' => 'constructor method result', 'transitions' => [
				'' => function(LT $token) {
					$returnType = $this->programBuilder->typeRegistry()['TypeByName']($this->s->result['typeName'])->stateType();
					$errorType = $this->s->result['error_type'] ?? null;
					$this->s->generated = $this->programBuilder->methodBuilder()['addMethod'](
						$this->programBuilder->typeRegistry()['TypeByName']('Constructor'),
						$this->s->result['typeName'],
						$this->s->result['parameter_type'],
						$this->s->result['dependency_type'] ?? null,
						$errorType ? $this->programBuilder->typeRegistry()['Result'](
							$returnType, $errorType
						) : $returnType,
						$this->programBuilder->expressionRegistry()['functionBody']($this->s->generated),
					);
					$this->s->move(102);
				}
			]],
			146 => ['name' => 'constructor method tuple or record parameter return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['parameter_type'] = $this->s->generated;
					$this->s->stay(144);
				}
			]],
			147 => ['name' => 'constructor method error type', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['error_type'] = $this->s->generated;
					$this->s->stay(148);
				}
			]],
			148 => ['name' => 'constructor method body after error type', 'transitions' => [
				T::dependency_marker->name => function(LT $token) {
					$this->s->push(152);
					$this->s->move(701);
				},
				T::function_body_marker->name => function(LT $token) {
					$this->s->push(145);
					$this->s->move(201);
				},
			]],
			151 => ['name' => 'method dependency result', 'transitions' => [
				T::function_body_marker->name => function(LT $token) {
					$this->s->result['dependency_type'] = $this->s->generated;
					$this->s->push(127);
					$this->s->move(201);
				},
			]],
			152 => ['name' => 'constructor dependency result', 'transitions' => [
				T::function_body_marker->name => function(LT $token) {
					$this->s->result['dependency_type'] = $this->s->generated;
					$this->s->push(145);
					$this->s->move(201);
				},
			]],
			153 => ['name' => 'cast dependency result', 'transitions' => [
				T::function_body_marker->name => function(LT $token) {
					$this->s->result['dependency_type'] = $this->s->generated;
					$this->s->push(121);
					$this->s->move(201);
				},
			]],

			201 => ['name' => 'expression adt start', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(302);
					$this->s->stay(301);
				}
			]],
			202 => ['name' => 'constant expression', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(203);
					$this->s->stay(401);
				}
			]],
			203 => ['name' => 'constant expression value', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->expressionRegistry()['constant']($this->s->generated);
					//$this->s->moveAndPop();
					$this->s->pop();
				}
			]],

			230 => ['name' => 'expression sequence', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['sequence_expressions'] = [];
					$this->s->push(231);
					$this->s->stay(201);
				}
			]],
			231 => ['name' => 'expression sequence separator', 'transitions' => [
				T::expression_separator->name => function(LT $token) {
					$this->s->result['sequence_expressions'][] = $this->s->generated;
					$this->s->push(231);
					$this->s->move(201);
				},
				T::sequence_end->name => function(LT $token) {
					$this->s->result['sequence_expressions'][] = $this->s->generated;
					$this->s->generated = $this->programBuilder->expressionRegistry()['sequence']($this->s->result['sequence_expressions']);
					$this->s->moveAndPop();
				},
			]],

			240 => ['name' => 'expression return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['sequence_expressions'] = [];
					$this->s->push(241);
					$this->s->stay(201);
				}
			]],
			241 => ['name' => 'expression sequence return', 'transitions' => [
				'' => function(LT $token) {
					$result = $this->s->generated;
					$this->s->generated = $this->programBuilder->expressionRegistry()['return']($result);
					$this->s->pop();
				},
			]],

			250 => ['name' => 'expression no error', 'transitions' => [
				T::call_start->name => function(LT $token) {
					$this->s->result['sequence_expressions'] = [];
					$this->s->push(251);
					$this->s->move(201);
				}
			]],
			251 => ['name' => 'expression no error return', 'transitions' => [
				T::call_end->name => function(LT $token) {
					$result = $this->s->generated;
					$this->s->generated = $this->programBuilder->expressionRegistry()['noError']($result);
					$this->s->moveAndPop();
				},
			]],
			260 => ['name' => 'var expression', 'transitions' => [
				T::assign->name => 261,
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->expressionRegistry()['var']($this->s->result['var_name']);
					$this->s->pop();
				}
			]],
			261 => ['name' => 'assign expression', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(262);
					$this->s->stay(201);
				}
			]],
			262 => ['name' => 'assign expression value', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->expressionRegistry()['assign']($this->s->result['var_name'], $this->s->generated);
					$this->s->pop();
				}
			]],
			270 => ['name' => 'type expression', 'transitions' => [
				T::property_accessor->name => function(LT $token) {
					$this->s->push(274);
					$this->s->back(401);
				},
				T::empty_tuple->name => function(LT $token) {
					$this->s->generated = $this->programBuilder->expressionRegistry()['constant'](
						$this->programBuilder->valueRegistry()['atom']($this->s->result['type_name'])
					);
					$this->s->moveAndPop();
				},
				T::call_start->name => 271,
				T::tuple_start->name => function(LT $token) {
					$this->s->push(273);
					$this->s->stay(201);
				},
			]],
			271 => ['name' => 'constructor call expression', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(272);
					$this->s->stay(201);
				}
			]],
			272 => ['name' => 'constructor call value', 'transitions' => [
				T::call_end->name => function(LT $token) {
					$this->s->generated = $this->programBuilder->expressionRegistry()['constructor']($this->s->result['type_name'], $this->s->generated);
					$this->s->moveAndPop();
				}
			]],
			273 => ['name' => 'constructor call value tuple or record', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->expressionRegistry()['constructor']($this->s->result['type_name'], $this->s->generated);
					$this->s->pop();
				}
			]],
			274 => ['name' => 'enum value', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->expressionRegistry()['constant']($this->s->generated);
					$this->s->pop();
				}
			]],

			280 => ['name' => 'list or dict expression', 'transitions' => [
				T::word->name => $c = function(LT $token) {
					$this->s->result['first_token'] = $token;
					$this->s->move(281);
				},
				T::var_keyword->name => $c,
				T::type_keyword->name => $c,
				T::null->name => $c,
				T::true->name => $c,
				T::false->name => $c,
				T::type->name => $c,
				'' => function(LT $token) {
					$this->s->stay(291);
				},
			]],
			281 => ['name' => 'dict expression separator', 'transitions' => [
				T::colon->name => 282,
				'' => function(LT $token) {
					$this->s->back(291);
				}
			]],
			282 => ['name' => 'dict expression expression', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['current_key'] ??= $this->s->result['first_token']->patternMatch->text;
					$this->s->push(283);
					$this->s->stay(201);
				},
			]],
			283 => ['name' => 'dict expression dict expression return point', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['compositeValues'][$this->s->result['current_key']] = $this->s->generated;
					$this->s->stay(284);
				}
			]],
			284 => ['name' => 'dict expression dict expression separator', 'transitions' => [
				T::tuple_end->name => 285,
				T::value_separator->name => 286,
			]],
			285 => ['name' => 'dict expression dict expression return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->expressionRegistry()['record'](
						$this->s->result['compositeValues']
					);
					$this->s->pop();
				},
			]],
			286 => ['name' => 'dict expression dict expression key', 'transitions' => [
				T::word->name => $c = function(LT $token) {
					$this->s->result['current_key'] = $token->patternMatch->text;
					$this->s->move(281);
				},
				T::var_keyword->name => $c,
				T::type_keyword->name => $c,
				T::null->name => $c,
				T::true->name => $c,
				T::false->name => $c,
				T::type->name => $c,
			]],
			291 => ['name' => 'list expression expression', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(292);
					$this->s->stay(201);
				},
			]],
			292 => ['name' => 'list expression list expression return point', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['compositeValues'][] = $this->s->generated;
					$this->s->stay(293);
				}
			]],
			293 => ['name' => 'list expression list expression separator', 'transitions' => [
				T::tuple_end->name => 294,
				T::value_separator->name => 291,
			]],
			294 => ['name' => 'list expression list expression return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->expressionRegistry()['tuple'](
						$this->s->result['compositeValues']
					);
					$this->s->pop();
				},
			]],


			301 => ['name' => 'expression start', 'transitions' => [
				T::string_value->name => $c = function(LT $token) { $this->s->stay(202); },
				T::positive_integer_number->name => $c,
				T::integer_number->name => $c,
				T::real_number->name => $c,
				T::empty_tuple->name => $c,
				T::empty_record->name => $c,
				T::null->name => $c,
				T::true->name => $c,
				T::false->name => $c,
				T::lambda_param->name => $c,
				T::type->name => $c,

				T::sequence_start->name => 230,
				T::lambda_return->name => 240,
				T::no_error->name => 250,

				T::var_keyword->name => function(LT $token) {
					$this->s->result = ['var_name' => $token->patternMatch->text];
					$this->s->move(260);
				},
				T::special_var->name => function(LT $token) {
					$this->s->generated = $this->programBuilder->expressionRegistry()['var']($token->patternMatch->text);
					$this->s->moveAndPop();
				},
				T::this_var->name => function(LT $token) {
					$this->s->generated = $this->programBuilder->expressionRegistry()['var']($token->patternMatch->text);
					$this->s->moveAndPop();
				},
				T::type_keyword->name => function(LT $token) {
					$this->s->result = ['type_name' => $token->patternMatch->text];
					$this->s->move(270);
				},
				T::tuple_start->name => function(LT $token) {
					$this->s->result['compositeValues'] = [];
					$this->s->move(280);
				},
				T::when_value_of->name => function(LT $token) {
					$this->s->result['matchType'] = 'matchValue';
					$this->s->result['matchPairs'] = [];
					$this->s->move(320);
				},
				T::when_type_of->name => function(LT $token) {
					$this->s->result['matchType'] = 'matchType';
					$this->s->result['matchPairs'] = [];
					$this->s->move(320);
				},
				T::when_is_true->name => function(LT $token) {
					$this->s->result['matchType'] = 'isTrue';
					$this->s->result['matchPairs'] = [];
					$this->s->move(324);
				},
			]],

			302 => ['name' => 'property method or call', 'transitions' => [
				T::property_accessor->name => function(LT $token) {
					$this->s->result = [];
					$this->s->result['expression_left'] = $this->s->generated;
					$this->s->move(303);
				},
				T::method_marker->name => function(LT $token) {
					$this->s->result = [];
					$this->s->result['is_no_error'] = false;
					$this->s->result['expression_left'] = $this->s->generated;
					$this->s->move(305);
				},
				T::lambda_return->name => function(LT $token) {
					$this->s->result = [];
					$this->s->result['is_no_error'] = true;
					$this->s->result['expression_left'] = $this->s->generated;
					$this->s->move(305);
				},
				T::call_start->name => function(LT $token) {
					$this->s->result = [];
					$this->s->result['expression_left'] = $this->s->generated;
					$this->s->move(311);
				},
				T::tuple_start->name => function(LT $token) {
					$this->s->result = [];
					$this->s->result['expression_left'] = $this->s->generated;
					$this->s->stay(313);
				},
				T::empty_tuple->name => function(LT $token) {
					$this->s->generated = $this->programBuilder->expressionRegistry()['call'](
						$this->s->generated,
						$this->programBuilder->expressionRegistry()['constant']($this->programBuilder->valueRegistry()['list']([]))
					);
					$this->s->move(315);
				},
				T::empty_record->name => function(LT $token) {
					$this->s->generated = $this->programBuilder->expressionRegistry()['call'](
						$this->s->generated,
						$this->programBuilder->expressionRegistry()['constant']($this->programBuilder->valueRegistry()['dict']([]))
					);
					$this->s->move(315);
				},
				T::arithmetic_op->name => $c = function(LT $token) {
					if ($token->patternMatch->text === '$') {
						$this->s->pop();
						return;
					}
					$this->s->result = [];
					$this->s->result['expression_left'] = $this->s->generated;
					$this->s->result['method_name'] = match($token->patternMatch->text) {
						'+' => 'binaryPlus',
						'-' => 'binaryMinus',
						'*' => 'binaryMultiply',
						'/' => 'binaryDivide',
						'%' => 'binaryModulo',
						'^' => 'binaryPower',
						'<' => 'binaryLessThan',
						'<=' => 'binaryLessThanEqual',
						'>' => 'binaryGreaterThan',
						'>=' => 'binaryGreaterThanEqual',
						'!=' => 'binaryNotEqual',
						'==' => 'binaryEqual',
						'||' => 'binaryOr',
						'&&' => 'binaryAnd',
					};
					$this->s->move(316);
				},
				T::lambda_param->name => $c,
				T::boolean_op->name => $c,
				T::less_than_equal->name => $c,
				T::greater_than_equal->name => $c,
				T::equals->name => $c,
				T::not_equals->name => $c,
				T::this_var->name => $c,
				T::special_var->name => $c,
				T::type_start->name => $c,
				T::type_end->name => $c,
				'' => function(LT $token) {
					$this->s->pop();
				},
			]],
			303 => ['name' => 'property name', 'transitions' => [
				T::var_keyword->name => $c = function(LT $token) {
					$this->s->generated = $this->programBuilder->expressionRegistry()['property'](
						$this->s->result['expression_left'],
						$token->patternMatch->text
					);
					$this->s->move(304);
				},
				T::type->name => $c,
				T::true->name => $c,
				T::false->name => $c,
				T::null->name => $c,
				T::method_marker->name => $c,
				T::lambda_return->name => $c,
				T::positive_integer_number->name => $c,
			]],
			304 => ['name' => 'property name next', 'transitions' => [
				T::property_accessor->name => $c = function(LT $token) {
					$this->s->stay(302);
				},
				T::boolean_op->name => $c,
				T::less_than_equal->name => $c,
				T::greater_than_equal->name => $c,
				T::arithmetic_op->name => $c,
				T::equals->name => $c,
				T::not_equals->name => $c,
				T::type_start->name => $c,
				T::this_var->name => $c,
				T::special_var->name => $c,
				T::method_marker->name => $c,
				T::lambda_return->name => $c,
				T::call_start->name => $c,
				T::tuple_start->name => $c,
				T::tuple_end->name => $c,
				T::type_start->name => $c,
				T::type_end->name => $c,
				'' => function(LT $token) {
					$this->s->pop();
				},
			]],
			305 => ['name' => 'method name', 'transitions' => [
				T::var_keyword->name => $c = function(LT $token) {
					$this->s->result['method_name'] = $token->patternMatch->text;
					$this->s->move(306);
				},
				T::type_keyword->name => $c,
				T::type->name => $c
			]],
			306 => ['name' => 'method name next', 'transitions' => [
				T::call_start->name => function(LT $token) {
					$this->s->move(307);
				},
				T::tuple_start->name => function(LT $token) {
					$this->s->stay(309);
				},
				T::property_accessor->name => $c = function(LT $token) {
					$this->noErrorMethodCall(false);
					$this->s->stay(302);
				},
				T::method_marker->name => $c,
				T::lambda_return->name => $c,
				'' => function(LT $token) {
					$this->noErrorMethodCall(false);
					$this->s->pop();
				},
			]],
			307 => ['name' => 'method call start', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(308);
					$this->s->stay(201);
				}
			]],
			308 => ['name' => 'method call value', 'transitions' => [
				T::call_end->name => function(LT $token) {
					$this->noErrorMethodCall(true);
					$this->s->move(315);
				}
			]],
			309 => ['name' => 'method call start tuple or record', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(310);
					$this->s->stay(201);
				}
			]],
			310 => ['name' => 'method call value tuple or record', 'transitions' => [
				T::property_accessor->name => $c = function(LT $token) {
					$this->noErrorMethodCall(true);
					$this->s->stay(302);
				},
				T::method_marker->name => $c,
				T::lambda_return->name => $c,
				T::call_start->name => $c,
				T::tuple_start->name => $c,
				'' => function(LT $token) {
					$this->noErrorMethodCall(true);
					$this->s->pop();
				}
			]],
			311 => ['name' => 'function call start', 'transitions' => [
				T::call_end->name => function(LT $token) {
					$this->s->generated = $this->programBuilder->expressionRegistry()['call'](
						$this->s->result['expression_left'],
						$this->programBuilder->expressionRegistry()['constant']($this->programBuilder->valueRegistry()['null']())
					);
					$this->s->move(315);
				},
				'' => function(LT $token) {
					$this->s->push(312);
					$this->s->stay(201);
				}
			]],
			312 => ['name' => 'function call value', 'transitions' => [
				T::call_end->name => function(LT $token) {
					$this->s->generated = $this->programBuilder->expressionRegistry()['call'](
						$this->s->result['expression_left'],
						$this->s->generated
					);
					$this->s->move(315);
				}
			]],
			313 => ['name' => 'function call start tuple or record', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(314);
					$this->s->stay(201);
				}
			]],
			314 => ['name' => 'function call value tuple or record', 'transitions' => [
				T::property_accessor->name => $c = function(LT $token) {
					$this->s->generated = $this->programBuilder->expressionRegistry()['call'](
						$this->s->result['expression_left'],
						$this->s->generated
					);
					$this->s->stay(302);
				},
				T::method_marker->name => $c,
				T::lambda_return->name => $c,
				T::call_start->name => $c,
				T::tuple_start->name => $c,
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->expressionRegistry()['call'](
						$this->s->result['expression_left'],
						$this->s->generated
					);
					$this->s->pop();
				}
			]],
			315 => ['name' => 'method call value', 'transitions' => [
				T::property_accessor->name => $c = function(LT $token) {
					$this->s->stay(302);
				},
				T::method_marker->name => $c,
				T::lambda_return->name => $c,
				T::call_start->name => $c,
				T::tuple_start->name => $c,
				'' => function(LT $token) {
					$this->s->pop();
				}
			]],

			316 => ['name' => 'method call arithmetic start', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(317);
					$this->s->stay(201);
				}
			]],
			317 => ['name' => 'method call arithmetic value', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->priorityMatch(
						$this->s->result['expression_left'],
						$this->s->result['method_name'],
						$this->s->generated
					);
					/*$this->s->generated = $this->programBuilder->expressionRegistry()['method'](
						$this->s->result['expression_left'],
						$this->s->result['method_name'],
						$this->s->generated
					);*/
					$this->s->stay(315);
				}
			]],

			320 => ['name' => 'match value of start', 'transitions' => [
				T::call_start->name => 321
			]],
			321 => ['name' => 'match value of target', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(322);
					$this->s->stay(201);
				}
			]],
			322 => ['name' => 'match value of target end', 'transitions' => [
				T::call_end->name => function(LT $token) {
					$this->s->result['matchTarget'] = $this->s->generated;
					$this->s->move(323);
				}
			]],
			323 => ['name' => 'match value is', 'transitions' => [
				T::when_value_is->name => 324
			]],
			324 => ['name' => 'match value of target end', 'transitions' => [
				T::sequence_start->name => 325
			]],
			325 => ['name' => 'match value pair start', 'transitions' => [
				T::default_match->name => 330,
				'' => function(LT $token) {
					$this->s->push(326);
					$this->s->stay(201);
				}
			]],
			326 => ['name' => 'match value pair match return', 'transitions' => [
				T::colon->name => function(LT $token) {
					$this->s->result['matchPairMatch'] = $this->s->generated;
					$this->s->move(327);
				}
			]],
			327 => ['name' => 'match value pair value start', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(328);
					$this->s->stay(201);
				}
			]],
			328 => ['name' => 'match value pair value return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['matchPairs'][] = $this->programBuilder->expressionRegistry()['matchPair'](
						$this->s->result['matchPairMatch'],
						$this->s->generated
					);
					$this->s->stay(329);
				}
			]],
			329 => ['name' => 'match value pair separator', 'transitions' => [
				T::value_separator->name => 325,
				T::sequence_end->name => 333
			]],
			330 => ['name' => 'match value pair match return', 'transitions' => [
				T::colon->name => 331
			]],
			331 => ['name' => 'match value default pair start', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(332);
					$this->s->stay(201);
				}
			]],
			332 => ['name' => 'match value pair value return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['matchPairs'][] = $this->programBuilder->expressionRegistry()['matchDefault'](
						$this->s->generated
					);
					$this->s->stay(329);
				}
			]],
			333 => ['name' => 'match value pair match return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = match($this->s->result['matchType']) {
						'isTrue' => $this->programBuilder->expressionRegistry()['matchTrue'](
							$this->s->result['matchPairs']
						),
						'matchType' => $this->programBuilder->expressionRegistry()['matchType'](
							$this->s->result['matchTarget'],
							$this->s->result['matchPairs']
						),
						'matchValue' => $this->programBuilder->expressionRegistry()['matchValue'](
							$this->s->result['matchTarget'],
							$this->s->result['matchPairs']
						),
					};
					$this->s->pop();
				}
			]],


			401 => ['name' => 'value start', 'transitions' => [
				T::string_value->name => function(LT $token) { $this->s->stay(420); },
				T::positive_integer_number->name => function(LT $token) { $this->s->stay(430); },
				T::integer_number->name => function(LT $token) { $this->s->stay(430); },
				T::real_number->name => function(LT $token) { $this->s->stay(440); },
				T::empty_tuple->name => function(LT $token) { $this->s->stay(402); },
				T::empty_record->name => function(LT $token) { $this->s->stay(403); },
				T::null->name => function(LT $token) { $this->s->stay(405); },
				T::true->name => function(LT $token) { $this->s->stay(406); },
				T::false->name => function(LT $token) { $this->s->stay(407); },
				T::lambda_param->name => function(LT $token) { $this->s->stay(450); },
				T::tuple_start->name => function(LT $token) {
					$this->s->result['compositeValues'] = [];
					$this->s->move(460);
				},
				T::type->name => function(LT $token) { $this->s->move(480); },
				T::type_keyword->name => function(LT $token) {
					$this->s->result['current_type_name'] = $token->patternMatch->text;
					$this->s->move(490);
				},
			]],

			402 => ['name' => 'empty list value', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->valueRegistry()['list']([]);
					$this->s->moveAndPop();
				},
			]],
			403 => ['name' => 'empty dict value', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->valueRegistry()['dict']([]);
					$this->s->moveAndPop();
				},
			]],
			405 => ['name' => 'null value', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->valueRegistry()['null']();
					$this->s->moveAndPop();
				},
			]],
			406 => ['name' => 'true value', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->valueRegistry()['true']();
					$this->s->moveAndPop();
				},
			]],
			407 => ['name' => 'false value', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->valueRegistry()['false']();
					$this->s->moveAndPop();
				},
			]],
			420 => ['name' => 'string value', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->valueRegistry()['string'](
						str_replace(['\`', '\n', '\\\\'], ["'", "\n", "\\"], substr($token->patternMatch->text, 1, -1))
					);
					$this->s->moveAndPop();
				},
			]],
			430 => ['name' => 'integer value', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->valueRegistry()['integer']((int)$token->patternMatch->text);
					$this->s->moveAndPop();
				},
			]],
			440 => ['name' => 'real value', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->valueRegistry()['real']((float)$token->patternMatch->text);
					$this->s->moveAndPop();
				},
			]],
			450 => ['name' => 'function value', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(451);
					$this->s->stay(501);
				},
			]],
			451 => ['name' => 'function value return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->pop();
				},
			]],
			460 => ['name' => 'list or dict value', 'transitions' => [
				T::word->name => $c = function(LT $token) {
					$this->s->result['first_token'] = $token;
					$this->s->move(461);
				},
				T::var_keyword->name => $c,
				T::type_keyword->name => $c,
				T::null->name => $c,
				T::true->name => $c,
				T::false->name => $c,
				T::type->name => $c,
				'' => function(LT $token) {
					$this->s->stay(471);
				},
			]],
			461 => ['name' => 'dict value separator', 'transitions' => [
				T::colon->name => 462,
				'' => function(LT $token) {
					$this->s->back(471);
				}
			]],
			462 => ['name' => 'dict value value', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['current_key'] ??= $this->s->result['first_token']->patternMatch->text;
					$this->s->push(463);
					$this->s->stay(401);
				},
			]],
			463 => ['name' => 'dict value dict value return point', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['compositeValues'][$this->s->result['current_key']] = $this->s->generated;
					$this->s->stay(464);
				}
			]],
			464 => ['name' => 'dict value dict value separator', 'transitions' => [
				T::tuple_end->name => 465,
				T::value_separator->name => 466,
			]],
			465 => ['name' => 'dict value dict value return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->valueRegistry()['dict'](
						$this->s->result['compositeValues']
					);
					$this->s->pop();
				},
			]],
			466 => ['name' => 'dict value dict value key', 'transitions' => [
				T::word->name => $c = function(LT $token) {
					$this->s->result['current_key'] = $token->patternMatch->text;
					$this->s->move(461);
				},
				T::var_keyword->name => $c,
				T::type_keyword->name => $c,
				T::null->name => $c,
				T::true->name => $c,
				T::false->name => $c,
				T::type->name => $c,
			]],
			471 => ['name' => 'list value value', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(472);
					$this->s->stay(401);
				},
			]],
			472 => ['name' => 'list value list value return point', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['compositeValues'][] = $this->s->generated;
					$this->s->stay(473);
				}
			]],
			473 => ['name' => 'list value list value separator', 'transitions' => [
				T::tuple_end->name => 474,
				T::value_separator->name => 471,
			]],
			474 => ['name' => 'list value list value return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->valueRegistry()['list'](
						$this->s->result['compositeValues']
					);
					$this->s->pop();
				},
			]],
			480 => ['name' => 'type value', 'transitions' => [
				T::tuple_start->name => function(LT $token) {
					$this->s->push(483);
					$this->s->stay(701);
				},
				T::sequence_start->name => 481,
			]],
			481 => ['name' => 'type value type', 'transitions' => [
				T::type_keyword->name => $c = function(LT $token) {
					$this->s->push(482);
					$this->s->stay(701);
				},
				T::tuple_start->name => $c,
				T::lambda_param->name => $c,
			]],
			482 => ['name' => 'type value type separator', 'transitions' => [
				T::sequence_end->name => 483,
			]],
			483 => ['name' => 'type value type return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->valueRegistry()['type']($this->s->generated);
					$this->s->pop();
				},
			]],
			490 => ['name' => 'value type name', 'transitions' => [
				T::empty_tuple->name => 491,
				T::property_accessor->name => 492,
			]],
			491 => ['name' => 'value atom', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->valueRegistry()['atom']($this->s->result['current_type_name']);
					$this->s->pop();
				}
			]],
			492 => ['name' => 'value enum', 'transitions' => [
				T::type_keyword->name => function(LT $token) {
					$this->s->generated = $this->programBuilder->valueRegistry()['enumeration'](
						$this->s->result['current_type_name'],
						$token->patternMatch->text
					);
					$this->s->moveAndPop();
				}
			]],

			501 => ['name' => 'function value start', 'transitions' => [
				T::lambda_param->name => 502
			]],
			502 => ['name' => 'function value parameter type', 'transitions' => [
				T::type_keyword->name => $c = function(LT $token) {
					$this->s->push(503);
					$this->s->stay(701);
				},
				T::tuple_start->name => $c,
				T::empty_tuple->name => $c,
				T::empty_record->name => $c,
			]],
			503 => ['name' => 'function value parameter return', 'transitions' => [
				T::lambda_return->name => function(LT $token) {
					$this->s->result['parameter'] = $this->s->generated;
					$this->s->move(504);
				}
			]],
			504 => ['name' => 'function value return type', 'transitions' => [
				T::type_keyword->name => $c = function(LT $token) {
					$this->s->push(505);
					$this->s->stay(701);
				},
				T::tuple_start->name => $c,
				T::lambda_param->name => $c,
			]],
			505 => ['name' => 'function value return return', 'transitions' => [
				T::function_body_marker->name => function(LT $token) {
					$this->s->result['return'] = $this->s->generated;
					$this->s->move(506);
				}
			]],
			506 => ['name' => 'function value body return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(507);
					$this->s->stay(201);
				},
			]],
			507 => ['name' => 'function value body return', 'transitions' => [
				'' => function(LT $token) {
					$return = $this->s->generated;
					$this->s->generated = $this->programBuilder->valueRegistry()['function'](
						$this->s->result['parameter'] ?? $this->programBuilder->typeRegistry()['Any'](),
						$this->s->result['return'] ?? $this->programBuilder->typeRegistry()['Any'](),
						$this->programBuilder->expressionRegistry()['functionBody']($return)
					);
					$this->s->pop();
				}
			]],


			701 => ['name' => 'type adt start', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(798);
					$this->s->stay(797);
				}
			]],
			798 => ['name' => 'union intersection check', 'transitions' => [
				T::union->name => function(LT $token) {
					$this->s->result = [];
					$this->s->result['union_left'] = $this->s->generated;
					$this->s->push(799);
					$this->s->move(797);
				},
				T::intersection->name => function(LT $token) {
					$this->s->result = [];
					$this->s->result['intersection_left'] = $this->s->generated;
					$this->s->push(796);
					$this->s->move(797);
				},
				'' => function(LT $token) {
					$this->s->pop();
				},
			]],
			796 => ['name' => 'union return', 'transitions' => [
				T::intersection->name => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeRegistry()['Intersection']([
						$this->s->result['intersection_left'],
						$this->s->generated
					], false);
					$this->s->stay(798);
				},
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeRegistry()['Intersection']([
						$this->s->result['intersection_left'],
						$this->s->generated
					], false);
					$this->s->pop();
				},
			]],
			799 => ['name' => 'union return', 'transitions' => [
				T::union->name => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeRegistry()['Union']([
						$this->s->result['union_left'],
						$this->s->generated
					], false);
					$this->s->stay(798);
				},
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeRegistry()['Union']([
						$this->s->result['union_left'],
						$this->s->generated
					], false);
					$this->s->pop();
				},
			]],
			797 => ['name' => 'type start', 'transitions' => [
				T::type_proxy_keyword->name => function(LT $token) {
					$type = substr($token->patternMatch->text, 1);
					$this->s->result['typeName'] = $type;
					$this->s->state = match($type) {
						'Integer' => 710,
						'Real' => 720,
						'String' => 730,
						'Array' => 740,
						'Map' => 750,
						'Type' => 760,
						'Mutable' => 770,
						'Result' => 780,
						'Error' => 775,
						'Any', 'Nothing', 'Boolean', 'True', 'False', 'Null' => 702,
						default => 789
					};
					$this->s->i++;
				},
				T::type_keyword->name => function(LT $token) {
					$this->s->result['typeName'] = $token->patternMatch->text;
					$this->s->state = match($token->patternMatch->text) {
						'Integer' => 710,
						'Real' => 720,
						'String' => 730,
						'Array' => 740,
						'Map' => 750,
						'Type' => 760,
						'Mutable' => 770,
						'Result' => 780,
						'Error' => 775,
						'Any', 'Nothing', 'Boolean', 'True', 'False', 'Null' => 702,
						default => 790
					};
					$this->s->i++;
				},
				T::empty_tuple->name => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeRegistry()['Tuple']([]);
					$this->s->moveAndPop();
				},
				T::empty_record->name => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeRegistry()['Record']([]);
					$this->s->moveAndPop();
				},
				T::call_start->name => 703,
				T::lambda_param->name => 901,
				T::tuple_start->name => function(LT $token) {
					$this->s->result['compositeValues'] = [];
					$this->s->move(812);
				},
			]],
			703 => ['name' => 'type open bracket', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(704);
					$this->s->stay(701);
				}
			]],
			704 => ['name' => 'type close bracket', 'transitions' => [
				T::call_end->name => function(LT $token) {
					$this->s->moveAndPop();
				}
			]],
			702 => ['name' => 'type basic', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeRegistry()[$this->s->result['typeName']]();
					$this->s->pop();
				},
			]],
			710 => ['name' => 'type integer', 'transitions' => [
				T::type_start->name => 711,
				T::tuple_start->name => 716,
				'' => function(LT $token) {
					$this->s->pop();
					$this->s->generated = $this->programBuilder->typeRegistry()['Integer']();
				},
			]],
			711 => ['name' => 'type integer range start', 'transitions' => [
				T::positive_integer_number->name => $c = function(LT $token) {
					$this->s->result['minValue'] = $token->patternMatch->text;
					$this->s->move(712);
				},
				T::integer_number->name => $c,
				T::range_dots->name => 713
			]],
			712 => ['name' => 'type integer range dots', 'transitions' => [
				T::range_dots->name => 713
			]],
			713 => ['name' => 'type integer range end', 'transitions' => [
				T::positive_integer_number->name => $c = function(LT $token) {
					$this->s->result['maxValue'] = $token->patternMatch->text;
					$this->s->move(714);
				},
				T::integer_number->name => $c,
				T::type_end->name => 715
			]],
			714 => ['name' => 'type integer type end', 'transitions' => [
				T::type_end->name => 715
			]],
			715 => ['name' => 'type integer return', 'transitions' => [
				'' => function(LT $token) {
					if (isset($this->s->result['subsetValues'])) {
						$this->s->generated = $this->programBuilder->typeRegistry()['IntegerSubset']($this->s->result['subsetValues']);
					} else {
						$this->s->generated = $this->programBuilder->typeRegistry()['Integer'](
							$this->s->result['minValue'] ?? MinusInfinity::value,
							$this->s->result['maxValue'] ?? PlusInfinity::value
						);
					}
					$this->s->pop();
				},
			]],
			716 => ['name' => 'type integer subset value', 'transitions' => [
				T::integer_number->name => $c = function(LT $token) {
					$this->s->result['subsetValues'] ??= [];
					$this->s->result['subsetValues'][] = $token->patternMatch->text;
					$this->s->move(717);
				},
				T::positive_integer_number->name => $c
			]],
			717 => ['name' => 'type integer subset separator', 'transitions' => [
				T::value_separator->name => 716,
				T::tuple_end->name => 715
			]],

			720 => ['name' => 'type real', 'transitions' => [
				T::type_start->name => 721,
				T::tuple_start->name => 726,
				'' => function(LT $token) {
					$this->s->pop();
					$this->s->generated = $this->programBuilder->typeRegistry()['Real']();
				},
			]],
			721 => ['name' => 'type real range start', 'transitions' => [
				T::positive_integer_number->name => $c = function(LT $token) {
					$this->s->result['minValue'] = $token->patternMatch->text;
					$this->s->move(722);
				},
				T::integer_number->name => $c,
				T::real_number->name => $c,
				T::range_dots->name => 723
			]],
			722 => ['name' => 'type real range dots', 'transitions' => [
				T::range_dots->name => 723
			]],
			723 => ['name' => 'type real range end', 'transitions' => [
				T::positive_integer_number->name => $c = function(LT $token) {
					$this->s->result['maxValue'] = $token->patternMatch->text;
					$this->s->move(724);
				},
				T::integer_number->name => $c,
				T::real_number->name => $c,
				T::type_end->name => 725
			]],
			724 => ['name' => 'type real type end', 'transitions' => [
				T::type_end->name => 725
			]],
			725 => ['name' => 'type real return', 'transitions' => [
				'' => function(LT $token) {
					if (isset($this->s->result['subsetValues'])) {
						$this->s->generated = $this->programBuilder->typeRegistry()['RealSubset']($this->s->result['subsetValues']);
					} else {
						$this->s->generated = $this->programBuilder->typeRegistry()['Real'](
							$this->s->result['minValue'] ?? MinusInfinity::value,
							$this->s->result['maxValue'] ?? PlusInfinity::value
						);
					}
					$this->s->pop();
				},
			]],
			726 => ['name' => 'type real subset value', 'transitions' => [
				T::real_number->name => $c = function(LT $token) {
					$this->s->result['subsetValues'] ??= [];
					$this->s->result['subsetValues'][] = $token->patternMatch->text;
					$this->s->move(727);
				},
				T::integer_number->name => $c,
				T::positive_integer_number->name => $c
			]],
			727 => ['name' => 'type real subset separator', 'transitions' => [
				T::value_separator->name => 726,
				T::tuple_end->name => 725
			]],

			730 => ['name' => 'type string', 'transitions' => [
				T::type_start->name => 731,
				T::tuple_start->name => 736,
				'' => function(LT $token) {
					$this->s->pop();
					$this->s->generated = $this->programBuilder->typeRegistry()['String']();
				},
			]],
			731 => ['name' => 'type string range start', 'transitions' => [
				T::positive_integer_number->name => function(LT $token) {
					$this->s->result['minLength'] = $token->patternMatch->text;
					$this->s->move(732);
				},
				T::range_dots->name => 733
			]],
			732 => ['name' => 'type string range dots', 'transitions' => [
				T::range_dots->name => 733
			]],
			733 => ['name' => 'type string range end', 'transitions' => [
				T::positive_integer_number->name => function(LT $token) {
					$this->s->result['maxLength'] = $token->patternMatch->text;
					$this->s->move(734);
				},
				T::type_end->name => 735
			]],
			734 => ['name' => 'type string type end', 'transitions' => [
				T::type_end->name => 735
			]],
			735 => ['name' => 'type string return', 'transitions' => [
				'' => function(LT $token) {
					if (isset($this->s->result['subsetValues'])) {
						$this->s->generated = $this->programBuilder->typeRegistry()['StringSubset']($this->s->result['subsetValues']);
					} else {
						$this->s->generated = $this->programBuilder->typeRegistry()['String'](
							$this->s->result['minLength'] ?? 0,
							$this->s->result['maxLength'] ?? PlusInfinity::value
						);
					}
					$this->s->pop();
				},
			]],
			736 => ['name' => 'type string subset value', 'transitions' => [
				T::string_value->name => function(LT $token) {
					$this->s->result['subsetValues'] ??= [];
					$this->s->result['subsetValues'][] = str_replace(['\`', '\n', '\\\\'], ["'", "\n", "\\"],
						substr($token->patternMatch->text, 1, -1));
					$this->s->move(737);
				},
			]],
			737 => ['name' => 'type string subset separator', 'transitions' => [
				T::value_separator->name => 736,
				T::tuple_end->name => 735
			]],

			740 => ['name' => 'type array', 'transitions' => [
				T::type_start->name => 741,
				'' => function(LT $token) {
					$this->s->pop();
					$this->s->generated = $this->programBuilder->typeRegistry()['Array']();
				},
			]],
			741 => ['name' => 'type array type or range', 'transitions' => [
				T::positive_integer_number->name => function(LT $token) {
					$this->s->stay(742);
				},
				T::range_dots->name => 744,
				'' => function(LT $token) {
					$this->s->push(747);
					$this->s->stay(701);
				},
			]],
			742 => ['name' => 'type array range', 'transitions' => [
				T::positive_integer_number->name => function(LT $token) {
					$this->s->result['minLength'] = $token->patternMatch->text;
					$this->s->move(743);
				},
			]],
			743 => ['name' => 'type array range dots', 'transitions' => [
				T::range_dots->name => 744
			]],
			744 => ['name' => 'type array range end', 'transitions' => [
				T::positive_integer_number->name => function(LT $token) {
					$this->s->result['maxLength'] = $token->patternMatch->text;
					$this->s->move(745);
				},
				T::type_end->name => 746
			]],
			745 => ['name' => 'type array type end', 'transitions' => [
				T::type_end->name => 746
			]],
			746 => ['name' => 'type array return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeRegistry()['Array'](
						$this->s->result['type'] ?? $this->programBuilder->typeRegistry()['Any'](),
						$this->s->result['minLength'] ?? 0,
						$this->s->result['maxLength'] ?? PlusInfinity::value
					);
					$this->s->pop();
				},
			]],
			747 => ['name' => 'type array return point', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['type'] = $this->s->generated;
					$this->s->stay(748);
				}
			]],
			748 => ['name' => 'type array separator', 'transitions' => [
				T::value_separator->name => 749,
				T::type_end->name => 746
			]],
			749 => ['name' => 'type array type or range', 'transitions' => [
				T::positive_integer_number->name => function(LT $token) {
					$this->s->stay(742);
				},
				T::range_dots->name => 744,
			]],

			750 => ['name' => 'type map', 'transitions' => [
				T::type_start->name => 751,
				'' => function(LT $token) {
					$this->s->pop();
					$this->s->generated = $this->programBuilder->typeRegistry()['Map']();
				},
			]],
			751 => ['name' => 'type map type or range', 'transitions' => [
				T::positive_integer_number->name => function(LT $token) {
					$this->s->stay(752);
				},
				T::range_dots->name => 754,
				'' => function(LT $token) {
					$this->s->push(757);
					$this->s->stay(701);
				},
			]],
			752 => ['name' => 'type map range', 'transitions' => [
				T::positive_integer_number->name => function(LT $token) {
					$this->s->result['minLength'] = $token->patternMatch->text;
					$this->s->move(753);
				}
			]],
			753 => ['name' => 'type map range dots', 'transitions' => [
				T::range_dots->name => 754
			]],
			754 => ['name' => 'type map range end', 'transitions' => [
				T::positive_integer_number->name => function(LT $token) {
					$this->s->result['maxLength'] = $token->patternMatch->text;
					$this->s->move(755);
				},
				T::type_end->name => 756
			]],
			755 => ['name' => 'type map type end', 'transitions' => [
				T::type_end->name => 756
			]],
			756 => ['name' => 'type map return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeRegistry()['Map'](
						$this->s->result['type'] ?? $this->programBuilder->typeRegistry()['Any'](),
						$this->s->result['minLength'] ?? 0,
						$this->s->result['maxLength'] ?? PlusInfinity::value
					);
					$this->s->pop();
				},
			]],
			757 => ['name' => 'type map return point', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['type'] = $this->s->generated;
					$this->s->stay(758);
				}
			]],
			758 => ['name' => 'type map separator', 'transitions' => [
				T::value_separator->name => 759,
				T::type_end->name => 756
			]],
			759 => ['name' => 'type map type or range', 'transitions' => [
				T::positive_integer_number->name => function(LT $token) {
					$this->s->stay(752);
				},
				T::range_dots->name => 754,
			]],

			760 => ['name' => 'type type', 'transitions' => [
				T::type_start->name => 761,
				'' => function(LT $token) {
					$this->s->pop();
					$this->s->generated = $this->programBuilder->typeRegistry()['Type'](
						$this->programBuilder->typeRegistry()['Any']()
					);
				},
			]],
			761 => ['name' => 'type type type', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(762);
					$this->s->stay(701);
				},
			]],
			762 => ['name' => 'type type return point', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['type'] = $this->s->generated;
					$this->s->stay(763);
				}
			]],
			763 => ['name' => 'type type separator', 'transitions' => [
				T::type_end->name => 764
			]],
			764 => ['name' => 'type type return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeRegistry()['Type'](
						$this->s->result['type'] ?? $this->programBuilder->typeRegistry()['Any'](),
					);
					$this->s->pop();
				},
			]],

			770 => ['name' => 'type mutable', 'transitions' => [
				T::type_start->name => 771,
				'' => function(LT $token) {
					$this->s->pop();
					$this->s->generated = $this->programBuilder->typeRegistry()['Mutable'](
						$this->programBuilder->typeRegistry()['Any']()
					);
				},
			]],
			771 => ['name' => 'type mutable type', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(772);
					$this->s->stay(701);
				},
			]],
			772 => ['name' => 'type mutable return point', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['type'] = $this->s->generated;
					$this->s->stay(773);
				}
			]],
			773 => ['name' => 'type mutable separator', 'transitions' => [
				T::type_end->name => 774
			]],
			774 => ['name' => 'type mutable return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeRegistry()['Mutable'](
						$this->s->result['type'] ?? $this->programBuilder->typeRegistry()['Any'](),
					);
					$this->s->pop();
				},
			]],

			775 => ['name' => 'type error', 'transitions' => [
				T::type_start->name => 776,
				'' => function(LT $token) {
					$this->s->pop();
					$this->s->generated = $this->programBuilder->typeRegistry()['Result'](
						$this->programBuilder->typeRegistry()['Nothing'](),
						$this->programBuilder->typeRegistry()['Any'](),
					);
				},
			]],
			776 => ['name' => 'type error type', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(777);
					$this->s->stay(701);
				},
			]],
			777 => ['name' => 'type error return point', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['type'] = $this->s->generated;
					$this->s->stay(778);
				}
			]],
			778 => ['name' => 'type error separator', 'transitions' => [
				T::type_end->name => 779
			]],
			779 => ['name' => 'type error return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeRegistry()['Result'](
						$this->programBuilder->typeRegistry()['Nothing'](),
						$this->s->result['type'] ?? $this->programBuilder->typeRegistry()['Any'](),
					);
					$this->s->pop();
				},
			]],

			780 => ['name' => 'type result', 'transitions' => [
				T::type_start->name => 781,
				'' => function(LT $token) {
					$this->s->pop();
					$this->s->generated = $this->programBuilder->typeRegistry()['Result'](
						$this->programBuilder->typeRegistry()['Any'](),
						$this->programBuilder->typeRegistry()['Any'](),
					);
				},
			]],
			781 => ['name' => 'type result type', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(782);
					$this->s->stay(701);
				},
			]],
			782 => ['name' => 'type result return point', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['type'] = $this->s->generated;
					$this->s->stay(783);
				}
			]],
			783 => ['name' => 'type result separator', 'transitions' => [
				T::value_separator->name => 784
			]],
			784 => ['name' => 'type result error type', 'transitions' => [
				'' => function(LT $token) {
					$this->s->push(785);
					$this->s->stay(701);
				},
			]],
			785 => ['name' => 'type result error return point', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['error_type'] = $this->s->generated;
					$this->s->stay(786);
				}
			]],
			786 => ['name' => 'type result separator', 'transitions' => [
				T::type_end->name => 787
			]],
			787 => ['name' => 'type result return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeRegistry()['Result'](
						$this->s->result['type'] ?? $this->programBuilder->typeRegistry()['Any'](),
						$this->s->result['error_type'] ?? $this->programBuilder->typeRegistry()['Any'](),
					);
					$this->s->pop();
				},
			]],

			789 => ['name' => 'type proxy basic', 'transitions' => [
				T::tuple_start->name => 791,
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeRegistry()['ProxyType']($this->s->result['typeName']);
					$this->s->pop();
				},
			]],
			790 => ['name' => 'type basic', 'transitions' => [
				T::tuple_start->name => 791,
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeRegistry()['NamedType']($this->s->result['typeName']);
					$this->s->pop();
				},
			]],
			791 => ['name' => 'type enum subset value', 'transitions' => [
				T::type_keyword->name => function(LT $token) {
					$this->s->result['subsetValues'] ??= [];
					$this->s->result['subsetValues'][] = $token->patternMatch->text;
					$this->s->move(792);
				},
			]],
			792 => ['name' => 'type string subset separator', 'transitions' => [
				T::value_separator->name => 791,
				T::tuple_end->name => 793
			]],
			793 => ['name' => 'type string return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeRegistry()['EnumerationSubset']($this->s->result['typeName'], $this->s->result['subsetValues']);
					$this->s->pop();
				},
			]],

			810 => ['name' => 'module level empty tuple', 'transitions' => [
				'expression_separator' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeBuilder()['addAlias']($this->s->result['typeName'],
						$this->programBuilder->typeRegistry()['Tuple']([])
					);
					$this->s->move(102);
				}
			]],
			811 => ['name' => 'module level empty record', 'transitions' => [
				'expression_separator' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeBuilder()['addAlias']($this->s->result['typeName'],
						$this->programBuilder->typeRegistry()['Record']([])
					);
					$this->s->move(102);
				}
			]],
			812 => ['name' => 'module level tuple or record', 'transitions' => [
				'type_keyword' => function(LT $token) {
					$this->s->result['first_token'] = $token;
					$this->s->move(813);
				},
				'word' => $c = function(LT $token) {
					$this->s->result['first_token'] = $token;
					$this->s->move(814);
				},
				'var_keyword' => $c,
				T::rest_type->name => 830,
				T::default_match->name => 824,
				T::colon->name => 834,
			]],
			813 => ['name' => 'module level tuple or record decider', 'transitions' => [
				T::colon->name => function(LT $token) {
					$this->s->move(815);
				},
				'' => function(LT $token) {
					$this->s->back(826);
				},
			]],
			814 => ['name' => 'module level record colon', 'transitions' => [
				T::colon->name => 815,
			]],
			815 => ['name' => 'module level record value type', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['current_key'] ??=
                        $this->s->result['first_token']->patternMatch->text;
					$this->s->push(816);
					$this->s->stay(701);
				},
			]],
			816 => ['name' => 'module level record value return point', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['compositeValues'][$this->s->result['current_key']] = $this->s->generated;
					$this->s->stay(817);
				}
			]],
			817 => ['name' => 'module level record value separator', 'transitions' => [
				T::tuple_end->name => 818,
				T::value_separator->name => 819,
			]],
			818 => ['name' => 'module level record value return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeRegistry()['Record'](
						$this->s->result['compositeValues']
					);
					$this->s->pop();
				},
			]],
			819 => ['name' => 'module level record key', 'transitions' => [
				T::word->name => $c = function(LT $token) {
					$this->s->result['current_key'] = $token->patternMatch->text;
					$this->s->move(814);
				},
				T::var_keyword->name => $c,
				T::type_keyword->name => $c,
				T::type->name => $c,
				T::true->name => $c,
				T::false->name => $c,
				T::null->name => $c,
				T::rest_type->name => 820,
				T::default_match->name => 824,
			]],
			820 => ['name' => 'module level record rest', 'transitions' => [
				T::tuple_end->name => 823,
				'' => function(LT $token) {
					$this->s->push(821);
					$this->s->stay(701);
				},
			]],
			821 => ['name' => 'module level record value rest return point', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['restType'] = $this->s->generated;
					$this->s->stay(822);
				}
			]],
			822 => ['name' => 'module level record value end', 'transitions' => [
				T::tuple_end->name => 823
			]],
			823 => ['name' => 'module level record value return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeRegistry()['Record'](
						$this->s->result['compositeValues'],
						$this->s->result['restType'] ?? $this->programBuilder->typeRegistry()['Any']()
					);
					$this->s->pop();
				},
			]],
			824 => ['name' => 'module level record key is type name', 'transitions' => [
				T::type_keyword->name => function(LT $token) {
					$typeName = $token->patternMatch->text;
					$recordKey = lcfirst($typeName);
					$this->s->result['compositeValues'][$recordKey] =
						$this->programBuilder->typeRegistry()['NamedType']($typeName);
					$this->s->move(817);
				},
			]],
			825 => ['name' => 'module level record value rest return point', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['restType'] = $this->s->generated;
					$this->s->stay(822);
				}
			]],

			826 => ['name' => 'module level tuple value type', 'transitions' => [
				T::rest_type->name => 830,
				'' => function(LT $token) {
					$this->s->push(827);
					$this->s->stay(701);
				},
			]],
			827 => ['name' => 'module level tuple value return point', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['compositeValues'][] = $this->s->generated;
					$this->s->stay(828);
				}
			]],
			828 => ['name' => 'module level tuple value separator', 'transitions' => [
				T::tuple_end->name => 829,
				T::value_separator->name => 826,
			]],
			829 => ['name' => 'module level tuple value return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeRegistry()['Tuple'](
						$this->s->result['compositeValues']
					);
					$this->s->pop();
				},
			]],
			830 => ['name' => 'module level tuple rest', 'transitions' => [
				T::tuple_end->name => 833,
				'' => function(LT $token) {
					$this->s->push(831);
					$this->s->stay(701);
				},
			]],
			831 => ['name' => 'module level tuple value rest return point', 'transitions' => [
				'' => function(LT $token) {
					$this->s->result['restType'] = $this->s->generated;
					$this->s->stay(832);
				}
			]],
			832 => ['name' => 'module level tuple value end', 'transitions' => [
				T::tuple_end->name => 833
			]],
			833 => ['name' => 'module level tuple value return', 'transitions' => [
				'' => function(LT $token) {
					$this->s->generated = $this->programBuilder->typeRegistry()['Tuple'](
						$this->s->result['compositeValues'],
						$this->s->result['restType'] ?? $this->programBuilder->typeRegistry()['Any']()
					);
					$this->s->pop();
				},
			]],
			834 => ['name' => 'module level tuple value end', 'transitions' => [
				T::rest_type->name => 820
			]],
			901 => ['name' => 'function type parameter type', 'transitions' => [
				T::type_keyword->name => $c = function(LT $token) {
					$this->s->push(902);
					$this->s->stay(701);
				},
				T::tuple_start->name => $c,
				T::empty_tuple->name => $c,
				T::empty_record->name => $c,
			]],
			902 => ['name' => 'function type parameter return', 'transitions' => [
				T::lambda_return->name => function(LT $token) {
					$this->s->result['parameter'] = $this->s->generated;
					$this->s->move(903);
				}
			]],
			903 => ['name' => 'function type return type', 'transitions' => [
				T::type_proxy_keyword->name => function(LT $token) {
					$this->s->push(904);
					$this->s->stay(701);
				},
				T::type_keyword->name => function(LT $token) {
					$this->s->push(904);
					$this->s->stay(701);
				},
				T::tuple_start->name => function(LT $token) {
					$this->s->push(904);
					$this->s->stay(701);
				},
			]],
			904 => ['name' => 'function type return return', 'transitions' => [
				'' => function(LT $token) {
					$return = $this->s->generated;
					$this->s->generated = $this->programBuilder->typeRegistry()['Function'](
						$this->s->result['parameter'] ?? $this->programBuilder->typeRegistry()['Any'](),
						$return ?? $this->programBuilder->typeRegistry()['Any']()
					);
					$this->s->pop();
				}
			]]
		];		
	}

	private const priorities = [
		'binaryPlus' => 5,
		'binaryMinus' => 5,
		'binaryMultiply' => 8,
		'binaryDivide' => 8,
		'binaryModulo' => 8,
		'binaryPower' => 10,
		'binaryLessThan' => 4,
		'binaryLessThanEqual' => 4,
		'binaryGreaterThan' => 4,
		'binaryGreaterThanEqual' => 4,
		'binaryNotEqual' => 3,
		'binaryEqual' => 3,
		'binaryOr' => 1,
		'binaryAnd' => 2,
	];

	private function priorityMatch(Expression $l, string $m, Expression $g): Expression {
		if ($g instanceof MethodCallExpression) {
			$gm = $g->methodName();
			$gmId = $gm->identifier;
			if (str_starts_with($gmId, 'binary')) {
				if ((self::priorities[$m] ?? 0) >= (self::priorities[$gmId] ?? 0)) {
					return $this->programBuilder->expressionRegistry()['method'](
						$this->programBuilder->expressionRegistry()['method'](
							$l, $m, $g->target()
						),
						$gmId,
						$g->parameter()
					);
				}
			}
		}
		return $this->programBuilder->expressionRegistry()['method']($l, $m, $g);
	}

	private function noErrorMethodCall(bool $useGenerated): void {
		$this->s->generated = $this->programBuilder->expressionRegistry()['method'](
			$this->s->result['expression_left'],
			$this->s->result['method_name'],
			$useGenerated ? $this->s->generated :
				$this->programBuilder->expressionRegistry()['constant']($this->programBuilder->valueRegistry()['null']())
		);
		if ($this->s->result['is_no_error'] ?? false) {
			$this->s->generated = $this->programBuilder->expressionRegistry()['noError']($this->s->generated);
		}
	}
}
