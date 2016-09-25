import urllib2
import urllib
import time
import json
from bs4 import BeautifulSoup
#http://ffp.sichuanair.com/FFPNewWeb/Mall/Detail/8761
#response = urllib2.urlopen("http://ffp.sichuanair.com/FFPNewWeb/Mall/Detail/6562")
#response = urllib2.urlopen("http://ffp.sichuanair.com/FFPNewWeb/Mall/Detail/8761")
#response = urllib2.urlopen("http://ffp.sichuanair.com/FFPNewWeb/Mall")
#print response.read()
#html = response.read()
baseurl='http://ffp.sichuanair.com'
getlist_url='http://ffp.sichuanair.com/FFPNewWeb/Mall/GetList'

html = open('index.html').read()
#print html
soup = BeautifulSoup(html, "html.parser")
#print soup.prettify()
for a in soup.select('.dl_Category'):
    for b in a.find_all('a'):
        #print b
        url=baseurl+b['href']
        if url != 'http://ffp.sichuanair.com/FFPNewWeb/Mall/List/SM':
            continue
        post_para={'ID':'SM', 'OrderType':'MIL', 'PageIndex':1, 'PageSize':0}
        post_data=urllib.urlencode(post_para)
        print url
        req=urllib2.Request(getlist_url, post_data)
        req.add_header('Content-Type', "application/x-www-form-urlencoded")
        response = urllib2.urlopen(req)
        cate_html=response.read()
        #print cate_html
        #cate_soup=BeautifulSoup(cate_html, "html.parser")   #category page
        #print cate_soup
        ajaxRet=json.loads(cate_html)
        print ajaxRet["Result"]
        print ajaxRet["Message"]
        for item in ajaxRet["ListJSON"]:
            print "id:%d name:%s qty:%d miles:%d"%(item["RecordID"], item["ProductName"], item["SockQty"], item["RedeemMiles"])
        time.sleep(1)

        #print "href=", url, "text=", b.get_text()
        #print b['href']
