import ddddocr,base64
import sys,logging
logging.getLogger().setLevel(logging.ERROR)
img_base64=sys.argv[1]
ocr=ddddocr.DdddOcr(show_ad=False)
print(ocr.classification(base64.b64decode(img_base64)),end='')