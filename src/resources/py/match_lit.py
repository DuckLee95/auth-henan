from image_util import base64_to_cv2, get_slider_length
from datetime import datetime,timezone,timedelta
import random
import numpy as np
import json,sys

def bezier_curve(p0, p1, p2, p3, num_points=100):
    """
    生成三阶贝塞尔曲线上的点。
    参数:
    p0, p1, p2, p3 -- 四个控制点，格式为 (x, y)
    num_points -- 曲线上的采样点数量
    返回:
    曲线上的点列表，每个点为 (x, y)
    """
    t = np.linspace(0, 1, num_points)
    curve = []

    for t_val in t:
        x = (1 - t_val)**3 * p0[0] + 3 * (1 - t_val)**2 * t_val * p1[0] + \
        3 * (1 - t_val) * t_val**2 * p2[0] + t_val**3 * p3[0]
        y = (1 - t_val)**3 * p0[1] + 3 * (1 - t_val)**2 * t_val * p1[1] + \
        3 * (1 - t_val) * t_val**2 * p2[1] + t_val**3 * p3[1]
        curve.append((x, y))

    return np.array(curve)

def generator_t(init_value=915):
    t=init_value
    while True:
        yield t
        t+=random.randint(5,20)

def get_track(x:int,y:int,type: str,t: int):
    return {
        "x":x,
        "y":y,
        "type":type,# "move","up","down"
        "t":t,
    }
def get_output(captcha_info):
    data=captcha_info['captcha']
    image = base64_to_cv2(data["backgroundImage"].replace("data:image/jpeg;base64,", ""))
    template = base64_to_cv2(data["templateImage"].replace("data:image/png;base64,", ""))
    move_length = int(get_slider_length(image, template) / 2)
    # 设置控制点
    p0 = [0, 0]
    p1 = [move_length/3, -5]
    p2 = [move_length/2, -25]
    p3 = [move_length, -17]
    # 计算贝塞尔曲线
    curve = bezier_curve(p0, p1, p2, p3)

    t=generator_t()
    track_list=[get_track(0,0,"down",next(t))]
    for x,y in curve:
        track_list.append(get_track(int(x),round(y),'move',next(t)))
    x,y=curve[-1]
    track_list.append(get_track(int(x),int(y),'up',next(t)))

    #output
    def format_time(time:datetime):
        return time.isoformat(timespec='milliseconds').replace('+00:00', 'Z')
    end_time=datetime.now(timezone.utc)
    start_time=end_time-timedelta(milliseconds=track_list[-1]["t"])
    output={
        "id":captcha_info['id'],
        "data":{
            'bgImageHeight':int(data['backgroundImageHeight']/2),
            'bgImageWidth':int(data['backgroundImageWidth']/2),
            'sliderImageHeight':int(data['templateImageHeight']/2),
            'sliderImageWidth':int(data['templateImageWidth']/2),
            'startSlidingTime':format_time(start_time),
            'endSlidingTime':format_time(end_time),
            'trackList':track_list,
        }
    }
    return output

captcha_info=json.loads(sys.argv[1])
print(json.dumps(get_output(captcha_info)))
# try:
#     captcha_info=json.loads(sys.argv[1])
#     print(json.dumps(get_output(captcha_info)))
# except:
#     print("python程序异常!")