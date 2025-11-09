#!/usr/bin/python3
import json
from image_util import base64_to_cv2,get_slider_length
import sys

try:
    img_json=json.loads(sys.argv[1])
    image_baes64=img_json['bigImage']
    template_base64=img_json['smallImage']

    image=base64_to_cv2(image_baes64)
    template=base64_to_cv2(template_base64)
    #template size
    h,w=template.shape[:2]
    #image size
    img_height,img_width=image.shape[:2]
    #matchTemplate
    move_length=get_slider_length(image,template)
    #比例缩放
    width=280

    move_length=(int)(move_length/img_width*width)
    output={
        "canvasLength":280,
        "moveLength":move_length,
    }
    print(json.dumps(output))
except:
    print("python程序异常")