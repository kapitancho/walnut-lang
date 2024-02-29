<?php

namespace Walnut\Lang\Implementation\Compilation;

enum Token: string {
	case code_comment = '\/\*.*?\*\/';
	case dependency_marker = '\%\%';
	case function_body_marker = '\:\:';
	case cast_marker = '\=\=\>';
	case method_marker = '\-\>';
	case rest_type = '\.\.\.';
	case range_dots = '\.\.';
	case not_equals = '\!\=';
	case equals = '\=\=';
	case subtype = '\<\:';
	case expression_separator = '\;';
	case value_separator = '\,';
	case atom_type = '\:\[]';
	case enum_type_start = '\:\[';
	case colon = '\:';
	case lambda_param = '\^';
	case lambda_return = '\=\>';
	case call_start = '\(';
	case call_end = '\)';
	case sequence_start = '\{';
	case sequence_end = '\}';
	case type_start = '\<';
	case type_end = '\>';
	case empty_tuple = '\[\]';
	case empty_record = '\[\:\]';
	case tuple_start = '\[';
	case tuple_end = '\]';
	case boolean_op = '(!|&&|\|\|)';
	case union = '\|';
	case intersection = '\&';
	case assign = '\=';
	case true = 'true';
	case false = 'false';
	case null = 'null';
	case type = 'type';
	case no_error = '\?noError';
	case when_type_of = '\?whenTypeOf\b';
	case when_is_true = '\?whenIsTrue\b';
	case when_value_of = '\?whenValueOf\b';
	case when_value_is = '\bis\b';
	case string_value = '\'.*?\'';
	case module_identifier = 'module [a-z][a-z0-9_-]+(\s*\%\%\s[a-z][a-z0-9_-]+(\s*\,\s*[a-z][a-z0-9_-]+)*)?\:';
	case type_proxy_keyword = '`[A-Z][a-zA-Z0-9_]*';
	case type_keyword = '[A-Z][a-zA-Z0-9_]*';
	case var_keyword = '[a-z][a-zA-Z0-9_]*';
	case this_var = '\$';
	case special_var = '[\%\#]';
	case real_number = '(0|(\-?[1-9][0-9]*))\.[0-9]+';
	case positive_integer_number = '0|([1-9][0-9]*)';
	case integer_number = '0|(\-?[1-9][0-9]*)';
	case arithmetic_op = '[\+\-\*\/]';
	case default_match = '\~';
	case property_accessor = '\.';
	case error_marker = '\@';
	case word = '[a-zA-z]+';
}