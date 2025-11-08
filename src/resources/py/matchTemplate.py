#!/usr/bin/python3
import cv2
import numpy as np
import base64,json
# import matplotlib.pyplot as plt

import sys

def base64_to_cv2(image_code):
    #解码
    image_data=base64.b64decode(image_code)
    #转为numpy
    image_array=np.frombuffer(image_data,np.uint8)
    #转为opencv
    image=cv2.imdecode(image_array,cv2.COLOR_RGBA2BGR)
    return image
def cv2_to_base64(image):
    #编码
    image_png=cv2.imencode('.png',image)[1]
    image_code=str(base64.b64encode(image_png)[2:-1])
    return image_code



try:
    img_json=json.loads(sys.argv[1])
    image=img_json['bigImage']
    template=img_json['smallImage']

    image=base64_to_cv2(image)
    template=base64_to_cv2(template)

    #gray
    image_gray=cv2.cvtColor(image,cv2.COLOR_BGR2GRAY)
    template_gray=cv2.cvtColor(template,cv2.COLOR_BGR2GRAY)

    #edge
    image_edge=cv2.Canny(image,100,200)
    template_edge=cv2.Canny(template,100,200)

    #template size
    h,w=template.shape[:2]
    #image size
    img_height,img_width=image.shape[:2]
    #matchTemplate
    result=cv2.matchTemplate(image_edge,template_edge,cv2.TM_CCOEFF_NORMED)
    min_val,max_val,min_loc,max_loc=cv2.minMaxLoc(result)
    top_left=max_loc
    bottom_right=(top_left[0]+w,top_left[1]+h)
    cv2.rectangle(image,top_left,bottom_right,(0,0,255),2)
    #比例缩放
    width=280
    move_length=(int)(top_left[0]/img_width*width)

    output={
        "canvasLength":280,
        "moveLength":move_length,
    }
    print(json.dumps(output))
except:
    print("python程序异常")

# #show TEST
# def show_images(*images):
#     fig,axs=plt.subplots(len(images[0]),len(images))
#     for i,image_list in enumerate(images):
#         for j,image in enumerate(image_list):
#             if len(images)>1:
#                 axs[j,i].imshow(image)
#             else:
#                 axs[j].imshow(image)
#     #移除坐标轴
#     for ax in axs.flat:
#         ax.axis('off')

#     plt.tight_layout()
#     plt.show()

# show_images([image,image_gray,image_edge],[template,template_gray,template_edge])
# cv2.destroyAllWindows()
