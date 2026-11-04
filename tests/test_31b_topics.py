import concurrent.futures,html,re,subprocess,urllib.request

sql="SELECT t.slug,tt.count,t.name FROM wp_terms t JOIN wp_term_taxonomy tt USING(term_id) WHERE tt.taxonomy='category' AND tt.count>0 ORDER BY t.slug"
out=subprocess.check_output(['docker','exec','-e','MYSQL_PWD=local31b','packrivo-31b-db','mysql','--default-character-set=utf8mb4','-uroot','www_packrivo_com','-N','-B','-e',sql],text=True)
terms=[(s,int(n),html.unescape(name)) for s,n,name in (line.split('\t') for line in out.splitlines())]
def check(term):
    slug,expected,name=term;path='/blog/topic/'+slug+'/'
    with urllib.request.urlopen('http://localhost:8082'+path,timeout=120) as response:
        body=response.read().decode('utf8','replace');status=response.status
    section=re.search(r'<main\b[^>]*>.*?<ul class="pk-list">(.*?)</ul>',body,re.I|re.S)
    links=re.findall(r'<li><a\b',section.group(1),re.I) if section else []
    canonical=re.findall(r'<link rel="canonical" href="([^"]+)"',body)
    titles=re.findall(r'<title>(.*?)</title>',body,re.I|re.S)
    passed=status==200 and len(links)==expected and canonical==['http://localhost:8082'+path] and len(titles)==1 and name in html.unescape(titles[0])
    return path,status,expected,len(links),canonical,passed
with concurrent.futures.ThreadPoolExecutor(max_workers=6) as pool:
    results=list(pool.map(check,terms))
for path,status,expected,actual,canonical,passed in results:print(path,'HTTP',status,'DB_POSTS',expected,'LINKS',actual,'CANONICAL',canonical[0] if canonical else 'MISSING','PASS' if passed else 'FAIL')
print('TOPICS',len(results),'RESULT', 'PASS' if all(x[-1] for x in results) else 'FAIL')
raise SystemExit(0 if all(x[-1] for x in results) else 1)
