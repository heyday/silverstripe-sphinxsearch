<% if $Indexes %><% loop $Indexes %>
source $Index
{
	type = mysql
	sql_host = $DB.server
	sql_user = $DB.username
	sql_pass = $DB.password
	sql_db = $DB.database
	sql_port = $DB.port
	sql_query  = $Query;
	<% if $Attributes %><% loop $Attributes %>
	sql_attr_{$Type} = $Attribute
	<% end_loop %><% end_if %>
}

index $Index
{
	source = $Index
	path = $IndexPath
	morphology = $Morphology
	enable_star = 1
	min_word_len = 1
	min_prefix_len = 3
	charset_type = utf-8
	stopwords = {$ConfigFolder}/stopwords.txt
}
<% end_loop %><% end_if %>

indexer
{
	mem_limit = $IndexerMemLimit
}

searchd
{
	listen = $Host:$Port
	log = {$LogFolder}/searchd.log
	query_log = {$LogFolder}/query.log
	read_timeout = 5
	max_children = 30
	pid_file = {$LogFolder}/searchd.pid
	max_matches = 1000
	seamless_rotate = 1
	preopen_indexes = 1
	unlink_old = 1
	compat_sphinxql_magics = 0
}