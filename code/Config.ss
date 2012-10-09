<% if Indexes %>
    <% control Indexes %>
    
source $Index
{	
    type = mysql
    sql_host = $server
    sql_user = $username
    sql_pass = $password
    sql_db = $database
    sql_port = $port
    sql_query  = $Query;
    <% if Attributes %>
        <% control Attributes %>
    sql_attr_$Type = $Attribute
        <% end_control %>    
    <% end_if %>
}

index $Index
{
    source = $Index
    path = $IndexPath
    morphology = none
    min_word_len = 1
    min_prefix_len = 3
    charset_type = utf-8
    stopwords = $ConfigFolder/stopwords.txt
}

    <% end_control %>
<% end_if %>

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
}