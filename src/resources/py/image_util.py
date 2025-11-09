import cv2
import numpy as np
import base64

def base64_to_cv2(image_code):
    image_code.replace("data:image/jpeg;base64,","")
    image_code.replace("data:image/png;base64,","")
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
def get_slider_length(image,template):
    #gray
    image_gray=cv2.cvtColor(image,cv2.COLOR_BGR2GRAY)
    template_gray=cv2.cvtColor(template,cv2.COLOR_BGR2GRAY)

    #edge
    image_edge=cv2.Canny(image_gray,100,200)
    template_edge=cv2.Canny(template_gray,100,200)
    #matchTemplate
    result=cv2.matchTemplate(image_edge,template_edge,cv2.TM_CCOEFF_NORMED)
    min_val,max_val,min_loc,max_loc=cv2.minMaxLoc(result)
    top_left=max_loc
    return top_left[0]