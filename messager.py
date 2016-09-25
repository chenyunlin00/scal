# -*- coding: utf-8 -*-
import pymongo
import time
import smtplib
from email.mime.text import MIMEText
msg = MIMEText("scal kindle arrive")
msg['Subject'] = u'kindel到货通知'
msg['From'] = "chenyunlin"
msg['To'] = "chenyunlin"
passwd=open("passwd.txt").read()

def sendmail():
    server = smtplib.SMTP("smtp.126.com", 25) # SMTP协议默认端口是25
    server.login("u_u_u@126.com", passwd) #iuoymxzzntcjbjaf
    server.sendmail("u_u_u@126.com", ["u_u_u@126.com"], msg.as_string())
    server.quit()

client = pymongo.MongoClient(host="localhost", port=27017)
db = client.scal_db
collection = db.scal_collection
items=db.items
while 1:
    kindle=items.find_one({"ProductName" : "亚马逊全新Kindle Paperwhite电子书阅读器"})
    if kindle != None:
        print kindle["SockQty"]
        #kindle["SockQty"] = 1
        if kindle["SockQty"] != 0:
            sendmail()
            break
        time.sleep(1)
client.close()
