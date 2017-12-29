#!/usr/bin/env python
#coding=utf-8
# -*- coding: utf-8 -*-
import urllib2
import urllib
import time
import json
import pymongo
from bs4 import BeautifulSoup
import sys
reload(sys)
sys.setdefaultencoding('utf-8')
urllib2.socket.setdefaulttimeout(30)

print "now %s" %( time.strftime('%Y-%m-%d %H:%M:%S',time.localtime(time.time())) )

#http://ffp.sichuanair.com/FFPNewWeb/Mall/Detail/8761
#response = urllib2.urlopen("http://ffp.sichuanair.com/FFPNewWeb/Mall/Detail/6562")
#response = urllib2.urlopen("http://ffp.sichuanair.com/FFPNewWeb/Mall/Detail/8761")
response = urllib2.urlopen("http://ffp.sichuanair.com/FFPNewWeb/Mall")
#print response.read()
html = response.read()
client = pymongo.MongoClient(host="localhost", port=27017)
db = client.scal_db
collection = db.scal_collection
items=db.items
baseurl='http://ffp.sichuanair.com'
getlist_url='http://ffp.sichuanair.com/FFPNewWeb/Mall/GetList'

#html = open('index.html').read()
#print html
soup = BeautifulSoup(html, "html.parser")
#print soup.prettify()
for a in soup.select('.dl_Category'):
    for b in a.find_all('a'):
        #print b
        #a[a.rfind("/")+1:len(a)]
        category = b['href']
        if category.rfind("/") == -1:
            continue
        category = category[category.rfind("/")+1:len(category)]
        #print "category=%s"%(category)
        url=baseurl+b['href']
        #if url != 'http://ffp.sichuanair.com/FFPNewWeb/Mall/List/SM':
        #    continue
        post_para={'ID':category, 'OrderType':'MIL', 'PageIndex':1, 'PageSize':0}
        post_data=urllib.urlencode(post_para)
        #print url
        req=urllib2.Request(getlist_url, post_data)
        req.add_header('Content-Type', "application/x-www-form-urlencoded")
        try:
            response = urllib2.urlopen(req)
            cate_html=response.read()
        except:
            print "get %s timeout"%(b['href'])
            continue
        #print cate_html
        #cate_soup=BeautifulSoup(cate_html, "html.parser")   #category page
        #print cate_soup
        ajaxRet=json.loads(cate_html)
        if ajaxRet["Result"] != True:
            continue
        #print ajaxRet["Result"]
        #print ajaxRet["Message"]
        for item in ajaxRet["ListJSON"]:
            findRet = items.find_one({"RecordID": item["RecordID"]})
            if findRet == None:
                print "Not found %s"%(item["RecordID"])
                items.insert_one(item)
            else:
                print "Update %s"%(item["RecordID"])
                items.update({"RecordID": item["RecordID"]}, item)
            print "id:%d name:%s qty:%d miles:%d"%(item["RecordID"], item["ProductName"], item["SockQty"], item["RedeemMiles"])
        time.sleep(5)
    time.sleep(5)

        #print "href=", url, "text=", b.get_text()
        #print b['href']
client.close()
