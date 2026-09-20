<?php
declare(strict_types=1);

date_default_timezone_set('America/New_York');

const REAL_FILE_NAME = 'mrl_vendor_quarantine_v001_20260920_022501pm.php';
const REAL_FILE_SHA256 = '74df6052fd905e7a87ac421229849cb05ca4165c0cb246063a5267c69ec8b3f1';
const REAL_FILE_B64 = 'PD9waHAKZGVjbGFyZShzdHJpY3RfdHlwZXM9MSk7CgovKioKICogbXJsX3ZlbmRvcl9xdWFyYW50aW5lX3YwMDFfMjAyNjA5MjBfMDIyNTAxcG0ucGhwCiAqCiAqIFZFUlNJT046IHYwMDEKICogR0VORVJBVEVEOiA5LzIwLzIwMjYgMjoyNTowMSBwbSBFVAogKgogKiBQVVJQT1NFOgogKiAtIFJldmVyc2libHkgcXVhcmFudGluZSB0aGUgcm9vdCAvcHVibGljX2h0bWwvdmVuZG9yIGRpcmVjdG9yeS4KICogLSBVc2VzIGEgc2FtZS1maWxlc3lzdGVtIHJlbmFtZSBpbnRvIC9wdWJsaWNfaHRtbC9fcXVhcmFudGluZS8uCiAqIC0gUHJvdmlkZXMgb25lLWNsaWNrIHJlc3RvcmUuCiAqIC0gRG9lcyBOT1QgdG91Y2ggV29yZFByZXNzIHBsdWdpbi90aGVtZSB2ZW5kb3IgdHJlZXMuCiAqIC0gRG9lcyBOT1QgbW9kaWZ5IGNvbXBvc2VyLmpzb24gb3IgYW55IFBIUCBmaWxlLgogKi8KCmRhdGVfZGVmYXVsdF90aW1lem9uZV9zZXQoJ0FtZXJpY2EvTmV3X1lvcmsnKTsKCmZ1bmN0aW9uIGgoJHZhbHVlKTogc3RyaW5nCnsKICAgIHJldHVybiBodG1sc3BlY2lhbGNoYXJzKChzdHJpbmcpJHZhbHVlLCBFTlRfUVVPVEVTLCAnVVRGLTgnKTsKfQoKZnVuY3Rpb24gZm10X2J5dGVzKGludCAkYnl0ZXMpOiBzdHJpbmcKewogICAgJHVuaXRzID0gWydCJywnS0InLCdNQicsJ0dCJ107CiAgICAkbiA9IChmbG9hdCkkYnl0ZXM7CiAgICAkaSA9IDA7CiAgICB3aGlsZSAoJG4gPj0gMTAyNCAmJiAkaSA8IGNvdW50KCR1bml0cyktMSkgewogICAgICAgICRuIC89IDEwMjQ7CiAgICAgICAgJGkrKzsKICAgIH0KICAgIHJldHVybiBudW1iZXJfZm9ybWF0KCRuLCAkaSA9PT0gMCA/IDAgOiAyKSAuICcgJyAuICR1bml0c1skaV07Cn0KCmZ1bmN0aW9uIGludmVudG9yeV9kaXIoc3RyaW5nICRkaXIpOiBhcnJheQp7CiAgICAkZmlsZXMgPSAwOwogICAgJGRpcnMgPSAwOwogICAgJGJ5dGVzID0gMDsKCiAgICBpZiAoIWlzX2RpcigkZGlyKSkgewogICAgICAgIHJldHVybiBbJ2ZpbGVzJz0+MCwnZGlycyc9PjAsJ2J5dGVzJz0+MF07CiAgICB9CgogICAgJGl0ID0gbmV3IFJlY3Vyc2l2ZUl0ZXJhdG9ySXRlcmF0b3IoCiAgICAgICAgbmV3IFJlY3Vyc2l2ZURpcmVjdG9yeUl0ZXJhdG9yKAogICAgICAgICAgICAkZGlyLAogICAgICAgICAgICBGaWxlc3lzdGVtSXRlcmF0b3I6OlNLSVBfRE9UUyB8IEZpbGVzeXN0ZW1JdGVyYXRvcjo6Q1VSUkVOVF9BU19GSUxFSU5GTwogICAgICAgICksCiAgICAgICAgUmVjdXJzaXZlSXRlcmF0b3JJdGVyYXRvcjo6U0VMRl9GSVJTVAogICAgKTsKCiAgICBmb3JlYWNoICgkaXQgYXMgJGluZm8pIHsKICAgICAgICBpZiAoJGluZm8tPmlzRGlyKCkpIHsKICAgICAgICAgICAgJGRpcnMrKzsKICAgICAgICB9IGVsc2VpZiAoJGluZm8tPmlzRmlsZSgpKSB7CiAgICAgICAgICAgICRmaWxlcysrOwogICAgICAgICAgICAkYnl0ZXMgKz0gKGludCkkaW5mby0+Z2V0U2l6ZSgpOwogICAgICAgIH0KICAgIH0KCiAgICByZXR1cm4gWydmaWxlcyc9PiRmaWxlcywnZGlycyc9PiRkaXJzLCdieXRlcyc9PiRieXRlc107Cn0KCiRkb2NSb290ID0gcnRyaW0oKHN0cmluZykoJF9TRVJWRVJbJ0RPQ1VNRU5UX1JPT1QnXSA/PyBfX0RJUl9fKSwgJy9cXCcpOwokc291cmNlID0gJGRvY1Jvb3QgLiAnL3ZlbmRvcic7CiRxdWFyYW50aW5lUm9vdCA9ICRkb2NSb290IC4gJy9fcXVhcmFudGluZSc7CiRkZXN0aW5hdGlvbiA9ICRxdWFyYW50aW5lUm9vdCAuICcvdmVuZG9yXzIwMjYwOTIwXzAyMjUwMXBtJzsKCiRhY3Rpb24gPSAoc3RyaW5nKSgkX1BPU1RbJ2FjdGlvbiddID8/ICcnKTsKJG1lc3NhZ2UgPSAnJzsKJG1lc3NhZ2VDbGFzcyA9ICdpbmZvJzsKCiRzb3VyY2VFeGlzdHMgPSBpc19kaXIoJHNvdXJjZSk7CiRkZXN0RXhpc3RzID0gaXNfZGlyKCRkZXN0aW5hdGlvbik7CgokaW52ZW50b3J5UGF0aCA9ICRzb3VyY2VFeGlzdHMgPyAkc291cmNlIDogKCRkZXN0RXhpc3RzID8gJGRlc3RpbmF0aW9uIDogJycpOwokaW52ZW50b3J5ID0gJGludmVudG9yeVBhdGggIT09ICcnID8gaW52ZW50b3J5X2RpcigkaW52ZW50b3J5UGF0aCkgOiBbJ2ZpbGVzJz0+MCwnZGlycyc9PjAsJ2J5dGVzJz0+MF07CgokY2FuUXVhcmFudGluZSA9ICRzb3VyY2VFeGlzdHMgJiYgISRkZXN0RXhpc3RzICYmIGlzX3dyaXRhYmxlKCRkb2NSb290KTsKJGNhblJlc3RvcmUgPSAhJHNvdXJjZUV4aXN0cyAmJiAkZGVzdEV4aXN0cyAmJiBpc193cml0YWJsZSgkZG9jUm9vdCk7CgppZiAoJGFjdGlvbiA9PT0gJ3F1YXJhbnRpbmUnKSB7CiAgICBpZiAoISRjYW5RdWFyYW50aW5lKSB7CiAgICAgICAgJG1lc3NhZ2UgPSAnUXVhcmFudGluZSBibG9ja2VkOiBwcmVmbGlnaHQgY29uZGl0aW9ucyBhcmUgbm90IHNhdGlzZmllZC4nOwogICAgICAgICRtZXNzYWdlQ2xhc3MgPSAnYmFkJzsKICAgIH0gZWxzZSB7CiAgICAgICAgaWYgKCFpc19kaXIoJHF1YXJhbnRpbmVSb290KSAmJiAhQG1rZGlyKCRxdWFyYW50aW5lUm9vdCwgMDc1NSwgdHJ1ZSkgJiYgIWlzX2RpcigkcXVhcmFudGluZVJvb3QpKSB7CiAgICAgICAgICAgICRtZXNzYWdlID0gJ1F1YXJhbnRpbmUgZmFpbGVkOiBjb3VsZCBub3QgY3JlYXRlIC9fcXVhcmFudGluZSBkaXJlY3RvcnkuJzsKICAgICAgICAgICAgJG1lc3NhZ2VDbGFzcyA9ICdiYWQnOwogICAgICAgIH0gZWxzZWlmICghQHJlbmFtZSgkc291cmNlLCAkZGVzdGluYXRpb24pKSB7CiAgICAgICAgICAgICRtZXNzYWdlID0gJ1F1YXJhbnRpbmUgZmFpbGVkOiB2ZW5kb3IgZGlyZWN0b3J5IGNvdWxkIG5vdCBiZSBtb3ZlZC4nOwogICAgICAgICAgICAkbWVzc2FnZUNsYXNzID0gJ2JhZCc7CiAgICAgICAgfSBlbHNlIHsKICAgICAgICAgICAgJG1lc3NhZ2UgPSAnUEFTUyDigJQgcm9vdCAvdmVuZG9yIG1vdmVkIGludG8gcXVhcmFudGluZS4gTm90aGluZyB3YXMgZGVsZXRlZC4nOwogICAgICAgICAgICAkbWVzc2FnZUNsYXNzID0gJ2dvb2QnOwogICAgICAgIH0KICAgIH0KfQoKaWYgKCRhY3Rpb24gPT09ICdyZXN0b3JlJykgewogICAgaWYgKCEkY2FuUmVzdG9yZSkgewogICAgICAgICRtZXNzYWdlID0gJ1Jlc3RvcmUgYmxvY2tlZDogcHJlZmxpZ2h0IGNvbmRpdGlvbnMgYXJlIG5vdCBzYXRpc2ZpZWQuJzsKICAgICAgICAkbWVzc2FnZUNsYXNzID0gJ2JhZCc7CiAgICB9IGVsc2VpZiAoIUByZW5hbWUoJGRlc3RpbmF0aW9uLCAkc291cmNlKSkgewogICAgICAgICRtZXNzYWdlID0gJ1Jlc3RvcmUgZmFpbGVkOiBxdWFyYW50aW5lZCB2ZW5kb3IgZGlyZWN0b3J5IGNvdWxkIG5vdCBiZSBtb3ZlZCBiYWNrLic7CiAgICAgICAgJG1lc3NhZ2VDbGFzcyA9ICdiYWQnOwogICAgfSBlbHNlIHsKICAgICAgICAkbWVzc2FnZSA9ICdQQVNTIOKAlCByb290IC92ZW5kb3IgcmVzdG9yZWQgZnJvbSBxdWFyYW50aW5lLic7CiAgICAgICAgJG1lc3NhZ2VDbGFzcyA9ICdnb29kJzsKICAgIH0KfQoKJHNvdXJjZUV4aXN0cyA9IGlzX2Rpcigkc291cmNlKTsKJGRlc3RFeGlzdHMgPSBpc19kaXIoJGRlc3RpbmF0aW9uKTsKJGludmVudG9yeVBhdGggPSAkc291cmNlRXhpc3RzID8gJHNvdXJjZSA6ICgkZGVzdEV4aXN0cyA/ICRkZXN0aW5hdGlvbiA6ICcnKTsKJGludmVudG9yeSA9ICRpbnZlbnRvcnlQYXRoICE9PSAnJyA/IGludmVudG9yeV9kaXIoJGludmVudG9yeVBhdGgpIDogWydmaWxlcyc9PjAsJ2RpcnMnPT4wLCdieXRlcyc9PjBdOwoKJGNhblF1YXJhbnRpbmUgPSAkc291cmNlRXhpc3RzICYmICEkZGVzdEV4aXN0cyAmJiBpc193cml0YWJsZSgkZG9jUm9vdCk7CiRjYW5SZXN0b3JlID0gISRzb3VyY2VFeGlzdHMgJiYgJGRlc3RFeGlzdHMgJiYgaXNfd3JpdGFibGUoJGRvY1Jvb3QpOwoKPz4KPCFkb2N0eXBlIGh0bWw+CjxodG1sIGxhbmc9ImVuIj4KPGhlYWQ+CjxtZXRhIGNoYXJzZXQ9InV0Zi04Ij4KPG1ldGEgbmFtZT0idmlld3BvcnQiIGNvbnRlbnQ9IndpZHRoPWRldmljZS13aWR0aCxpbml0aWFsLXNjYWxlPTEiPgo8dGl0bGU+TVJMIFZlbmRvciBRdWFyYW50aW5lPC90aXRsZT4KPHN0eWxlPgo6cm9vdHtjb2xvci1zY2hlbWU6ZGFyazstLWJnOiMxMDEwMTA7LS1wYW5lbDojMWIxYjFiOy0tbGluZTojM2QzZDNkOy0tdGV4dDojZWVlOy0tbXV0ZWQ6I2FhYTstLWdvb2Q6IzY2ZGY4ZDstLWJhZDojZmY3NDc0Oy0tZ29sZDojZjJjOThlfQoqe2JveC1zaXppbmc6Ym9yZGVyLWJveH0KYm9keXttYXJnaW46MDtwYWRkaW5nOjIycHg7YmFja2dyb3VuZDp2YXIoLS1iZyk7Y29sb3I6dmFyKC0tdGV4dCk7Zm9udC1mYW1pbHk6QXJpYWwsSGVsdmV0aWNhLHNhbnMtc2VyaWZ9Ci53cmFwe21heC13aWR0aDoxMDUwcHg7bWFyZ2luOjAgYXV0b30KaDF7bWFyZ2luOjAgMCA2cHg7Y29sb3I6dmFyKC0tZ29sZCl9Cmgye21hcmdpbjowIDAgMTBweH0KLmNhcmR7bWFyZ2luOjE0cHggMDtwYWRkaW5nOjE2cHg7YmFja2dyb3VuZDp2YXIoLS1wYW5lbCk7Ym9yZGVyOjFweCBzb2xpZCB2YXIoLS1saW5lKTtib3JkZXItcmFkaXVzOjE0cHh9Ci5ub3RpY2V7bWFyZ2luOjE0cHggMDtwYWRkaW5nOjEycHggMTRweDtib3JkZXItcmFkaXVzOjEwcHg7Ym9yZGVyOjFweCBzb2xpZCAjMzY1MDZmO2JhY2tncm91bmQ6IzE0MjAzM30KLm5vdGljZS5nb29ke2JvcmRlci1jb2xvcjojMzI3YTRiO2JhY2tncm91bmQ6IzEzMjcxYTtjb2xvcjojYThlZmJmfQoubm90aWNlLmJhZHtib3JkZXItY29sb3I6Izk4M2YzZjtiYWNrZ3JvdW5kOiMyYTE1MTU7Y29sb3I6I2ZmYjBiMH0KLmdvb2R7Y29sb3I6dmFyKC0tZ29vZCk7Zm9udC13ZWlnaHQ6ODAwfS5iYWR7Y29sb3I6dmFyKC0tYmFkKTtmb250LXdlaWdodDo4MDB9Lm11dGVke2NvbG9yOnZhcigtLW11dGVkKX0KY29kZXtiYWNrZ3JvdW5kOiMyODI4Mjg7cGFkZGluZzoycHggNXB4O2JvcmRlci1yYWRpdXM6NXB4O3dvcmQtYnJlYWs6YnJlYWstd29yZH0KdGFibGV7d2lkdGg6MTAwJTtib3JkZXItY29sbGFwc2U6Y29sbGFwc2V9CnRoLHRke3BhZGRpbmc6OXB4IDEwcHg7Ym9yZGVyLWJvdHRvbToxcHggc29saWQgIzMzMzt0ZXh0LWFsaWduOmxlZnQ7dmVydGljYWwtYWxpZ246dG9wfQp0aHtjb2xvcjp2YXIoLS1nb2xkKX0KLmJ1dHRvbnN7ZGlzcGxheTpmbGV4O2ZsZXgtd3JhcDp3cmFwO2dhcDoxMHB4O21hcmdpbi10b3A6MTRweH0KYnV0dG9ue2JvcmRlcjowO2JvcmRlci1yYWRpdXM6OXB4O3BhZGRpbmc6MTBweCAxNnB4O2NvbG9yOiNmZmY7Zm9udC13ZWlnaHQ6NzAwO2N1cnNvcjpwb2ludGVyO2ZvbnQtc2l6ZToxNHB4fQoucXVhcmFudGluZXtiYWNrZ3JvdW5kOiNiMDZhMTh9LnJlc3RvcmV7YmFja2dyb3VuZDojMjc2ZmNhfQpidXR0b246ZGlzYWJsZWR7b3BhY2l0eTouMzg7Y3Vyc29yOm5vdC1hbGxvd2VkfQp1bHtsaW5lLWhlaWdodDoxLjV9Cjwvc3R5bGU+CjwvaGVhZD4KPGJvZHk+CjxkaXYgY2xhc3M9IndyYXAiPgogICAgPGgxPk1STCBWZW5kb3IgUXVhcmFudGluZTwvaDE+CiAgICA8ZGl2IGNsYXNzPSJtdXRlZCI+djAwMSDCtyBnZW5lcmF0ZWQgOS8yMC8yMDI2IDI6MjU6MDEgcG0gRVQ8L2Rpdj4KCiAgICA8P3BocCBpZiAoJG1lc3NhZ2UgIT09ICcnKTogPz4KICAgICAgICA8ZGl2IGNsYXNzPSJub3RpY2UgPD9waHAgZWNobyBoKCRtZXNzYWdlQ2xhc3MpOyA/PiI+PD9waHAgZWNobyBoKCRtZXNzYWdlKTsgPz48L2Rpdj4KICAgIDw/cGhwIGVuZGlmOyA/PgoKICAgIDxkaXYgY2xhc3M9ImNhcmQiPgogICAgICAgIDxoMj5XaGF0IHdpbGwgbW92ZTwvaDI+CiAgICAgICAgPHA+CiAgICAgICAgICAgIFNvdXJjZTogPGNvZGU+PD9waHAgZWNobyBoKCRzb3VyY2UpOyA/PjwvY29kZT48YnI+CiAgICAgICAgICAgIFF1YXJhbnRpbmU6IDxjb2RlPjw/cGhwIGVjaG8gaCgkZGVzdGluYXRpb24pOyA/PjwvY29kZT4KICAgICAgICA8L3A+CiAgICAgICAgPHAgY2xhc3M9Im11dGVkIj4KICAgICAgICAgICAgVGhpcyBpcyBhIHNhbWUtZmlsZXN5c3RlbSBkaXJlY3RvcnkgcmVuYW1lLiBObyB2ZW5kb3IgZmlsZXMgYXJlIGRlbGV0ZWQgb3IgcmV3cml0dGVuLgogICAgICAgIDwvcD4KICAgIDwvZGl2PgoKICAgIDxkaXYgY2xhc3M9ImNhcmQiPgogICAgICAgIDxoMj5DdXJyZW50IHN0YXRlPC9oMj4KICAgICAgICA8dGFibGU+CiAgICAgICAgICAgIDx0cj48dGg+Q2hlY2s8L3RoPjx0aD5TdGF0dXM8L3RoPjx0aD5EZXRhaWw8L3RoPjwvdHI+CiAgICAgICAgICAgIDx0cj4KICAgICAgICAgICAgICAgIDx0ZD5Sb290IC92ZW5kb3I8L3RkPgogICAgICAgICAgICAgICAgPHRkIGNsYXNzPSI8P3BocCBlY2hvICRzb3VyY2VFeGlzdHMgPyAnZ29vZCcgOiAnbXV0ZWQnOyA/PiI+PD9waHAgZWNobyAkc291cmNlRXhpc3RzID8gJ1BSRVNFTlQnIDogJ0FCU0VOVCc7ID8+PC90ZD4KICAgICAgICAgICAgICAgIDx0ZD48Y29kZT48P3BocCBlY2hvIGgoJHNvdXJjZSk7ID8+PC9jb2RlPjwvdGQ+CiAgICAgICAgICAgIDwvdHI+CiAgICAgICAgICAgIDx0cj4KICAgICAgICAgICAgICAgIDx0ZD5RdWFyYW50aW5lIGNvcHk8L3RkPgogICAgICAgICAgICAgICAgPHRkIGNsYXNzPSI8P3BocCBlY2hvICRkZXN0RXhpc3RzID8gJ2dvb2QnIDogJ211dGVkJzsgPz4iPjw/cGhwIGVjaG8gJGRlc3RFeGlzdHMgPyAnUFJFU0VOVCcgOiAnQUJTRU5UJzsgPz48L3RkPgogICAgICAgICAgICAgICAgPHRkPjxjb2RlPjw/cGhwIGVjaG8gaCgkZGVzdGluYXRpb24pOyA/PjwvY29kZT48L3RkPgogICAgICAgICAgICA8L3RyPgogICAgICAgICAgICA8dHI+CiAgICAgICAgICAgICAgICA8dGQ+SW52ZW50b3J5PC90ZD4KICAgICAgICAgICAgICAgIDx0ZD48P3BocCBlY2hvIChpbnQpJGludmVudG9yeVsnZmlsZXMnXTsgPz4gZmlsZXMgLyA8P3BocCBlY2hvIChpbnQpJGludmVudG9yeVsnZGlycyddOyA/PiBmb2xkZXJzPC90ZD4KICAgICAgICAgICAgICAgIDx0ZD48P3BocCBlY2hvIGgoZm10X2J5dGVzKChpbnQpJGludmVudG9yeVsnYnl0ZXMnXSkpOyA/PjwvdGQ+CiAgICAgICAgICAgIDwvdHI+CiAgICAgICAgPC90YWJsZT4KCiAgICAgICAgPGRpdiBjbGFzcz0iYnV0dG9ucyI+CiAgICAgICAgICAgIDxmb3JtIG1ldGhvZD0icG9zdCIgb25zdWJtaXQ9InJldHVybiBjb25maXJtKCdNb3ZlIHJvb3QgL3ZlbmRvciBpbnRvIHF1YXJhbnRpbmU/IE5vdGhpbmcgd2lsbCBiZSBkZWxldGVkLicpOyI+CiAgICAgICAgICAgICAgICA8aW5wdXQgdHlwZT0iaGlkZGVuIiBuYW1lPSJhY3Rpb24iIHZhbHVlPSJxdWFyYW50aW5lIj4KICAgICAgICAgICAgICAgIDxidXR0b24gY2xhc3M9InF1YXJhbnRpbmUiIHR5cGU9InN1Ym1pdCIgPD9waHAgZWNobyAkY2FuUXVhcmFudGluZSA/ICcnIDogJ2Rpc2FibGVkJzsgPz4+UXVhcmFudGluZSBSb290IC92ZW5kb3I8L2J1dHRvbj4KICAgICAgICAgICAgPC9mb3JtPgoKICAgICAgICAgICAgPGZvcm0gbWV0aG9kPSJwb3N0IiBvbnN1Ym1pdD0icmV0dXJuIGNvbmZpcm0oJ1Jlc3RvcmUgcm9vdCAvdmVuZG9yIGZyb20gcXVhcmFudGluZT8nKTsiPgogICAgICAgICAgICAgICAgPGlucHV0IHR5cGU9ImhpZGRlbiIgbmFtZT0iYWN0aW9uIiB2YWx1ZT0icmVzdG9yZSI+CiAgICAgICAgICAgICAgICA8YnV0dG9uIGNsYXNzPSJyZXN0b3JlIiB0eXBlPSJzdWJtaXQiIDw/cGhwIGVjaG8gJGNhblJlc3RvcmUgPyAnJyA6ICdkaXNhYmxlZCc7ID8+PlJlc3RvcmUgUm9vdCAvdmVuZG9yPC9idXR0b24+CiAgICAgICAgICAgIDwvZm9ybT4KICAgICAgICA8L2Rpdj4KICAgIDwvZGl2PgoKICAgIDxkaXYgY2xhc3M9ImNhcmQiPgogICAgICAgIDxoMj5XaHkgdGhpcyBpcyByZWFkeSBmb3IgcXVhcmFudGluZTwvaDI+CiAgICAgICAgPHVsPgogICAgICAgICAgICA8bGk+VGhlIHRhcmdldGVkIHByb2R1Y3Rpb24gYXVkaXQgZm91bmQgPHN0cm9uZz56ZXJvIFBIUCBpbmNsdWRlL3JlcXVpcmUgc3RhdGVtZW50czwvc3Ryb25nPiBhbW9uZyB0aGUgbmluZSByZW1haW5pbmcgcmV2aWV3IGZpbGVzIHRoYXQgcmVmZXJlbmNlZCB2ZW5kb3IuPC9saT4KICAgICAgICAgICAgPGxpPjxjb2RlPmNwYXNzLnBocDwvY29kZT4sIDxjb2RlPmZwYXNzLnBocDwvY29kZT4sIDxjb2RlPnJlZ2lzdGVyLnBocDwvY29kZT4sIDxjb2RlPnZlcmlmeS5waHA8L2NvZGU+LCBhbmQgPGNvZGU+anMvam90Zm9ybS5qczwvY29kZT4gcG9pbnQgdG8gPGNvZGU+anMvdmVuZG9yLy4uLjwvY29kZT4sIHdoaWNoIGlzIGEgc2VwYXJhdGUgSmF2YVNjcmlwdCBkaXJlY3RvcnkuPC9saT4KICAgICAgICAgICAgPGxpPjxjb2RlPndlZWtseV9zdGFuZGluZ3MucGhwPC9jb2RlPiBhbmQgaXRzIHYwNjUgY29weSBvbmx5IG1lbnRpb24gUGhwU3ByZWFkc2hlZXQgaW4gY29tbWVudHMuPC9saT4KICAgICAgICAgICAgPGxpPjxjb2RlPnRlYW1fY2hhcnQucGhwPC9jb2RlPiBvbmx5IG1lbnRpb25zIHRoZSBvbGQgZGVwZW5kZW5jeSBpbiBjb21tZW50cy9jaGFuZ2Vsb2cgYWZ0ZXIgdjAyMy48L2xpPgogICAgICAgICAgICA8bGk+PGNvZGU+Y29tcG9zZXIuanNvbjwvY29kZT4gaXMgYSBkZXBlbmRlbmN5IG1hbmlmZXN0LCBub3QgYSBydW50aW1lIGluY2x1ZGUuPC9saT4KICAgICAgICA8L3VsPgogICAgPC9kaXY+CgogICAgPGRpdiBjbGFzcz0iY2FyZCI+CiAgICAgICAgPGgyPk5vdCB0b3VjaGVkPC9oMj4KICAgICAgICA8dWw+CiAgICAgICAgICAgIDxsaT5Xb3JkUHJlc3MgY29yZSwgcGx1Z2lucywgdGhlbWVzLCBhbmQgdGhlaXIgb3duIHZlbmRvciBmb2xkZXJzLjwvbGk+CiAgICAgICAgICAgIDxsaT48Y29kZT5jb21wb3Nlci5qc29uPC9jb2RlPi48L2xpPgogICAgICAgICAgICA8bGk+QW55IFBIUCwgSlMsIGRhdGFiYXNlLCBzY2hlZHVsZXIsIGNyb24sIG9yIGNvbmZpZ3VyYXRpb24gZmlsZS48L2xpPgogICAgICAgIDwvdWw+CiAgICA8L2Rpdj4KPC9kaXY+CjwvYm9keT4KPC9odG1sPgo=';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function lint_php_file(string $path): array
{
    if (!function_exists('shell_exec')) {
        return ['ok'=>false,'output'=>'shell_exec() is not available.'];
    }
    $output = @shell_exec('php -l ' . escapeshellarg($path) . ' 2>&1');
    if ($output === null) {
        return ['ok'=>false,'output'=>'[NULL returned by shell_exec()]'];
    }
    $output = trim((string)$output);
    return ['ok'=>stripos($output,'No syntax errors detected') !== false,'output'=>$output];
}

$realPath = __DIR__ . '/' . REAL_FILE_NAME;
$action = (string)($_POST['action'] ?? '');
$message = '';
$messageClass = 'info';
$lint = null;

if ($action === 'stage') {
    $payload = base64_decode(REAL_FILE_B64, true);

    if ($payload === false) {
        $message = 'FAIL — embedded installer payload could not be decoded.';
        $messageClass = 'bad';
    } elseif (hash('sha256', $payload) !== REAL_FILE_SHA256) {
        $message = 'FAIL — embedded installer payload hash did not match.';
        $messageClass = 'bad';
    } else {
        if (is_file($realPath)) {
            $existing = (string)@file_get_contents($realPath);
            if (hash('sha256', $existing) !== REAL_FILE_SHA256) {
                $message = 'FAIL — a different file already exists with the installer filename. Nothing was overwritten.';
                $messageClass = 'bad';
            }
        }

        if ($message === '' && !is_file($realPath)) {
            if (@file_put_contents($realPath, $payload, LOCK_EX) === false) {
                $message = 'FAIL — could not stage the real installer.';
                $messageClass = 'bad';
            } else {
                @chmod($realPath, 0644);
            }
        }

        if ($message === '') {
            $lint = lint_php_file($realPath);
            if (!empty($lint['ok'])) {
                $message = 'PASS — real installer staged, hash-matched, and PHP lint passed.';
                $messageClass = 'good';
            } else {
                $message = 'FAIL — real installer was staged but did not pass PHP lint.';
                $messageClass = 'bad';
            }
        }
    }
}

if ($lint === null && is_file($realPath)) {
    $existing = (string)@file_get_contents($realPath);
    if (hash('sha256', $existing) === REAL_FILE_SHA256) {
        $lint = lint_php_file($realPath);
    }
}

$ready = is_file($realPath)
    && $lint !== null
    && !empty($lint['ok'])
    && hash_file('sha256', $realPath) === REAL_FILE_SHA256;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Vendor Quarantine Lint Gate</title>
<style>
:root{color-scheme:dark;--bg:#101010;--panel:#1b1b1b;--line:#3d3d3d;--text:#eee;--muted:#aaa;--good:#66df8d;--bad:#ff7474;--gold:#f2c98e}
*{box-sizing:border-box}
body{margin:0;padding:22px;background:var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif}
.wrap{max-width:950px;margin:0 auto}
h1{color:var(--gold);margin:0 0 6px}
.card{margin:14px 0;padding:16px;background:var(--panel);border:1px solid var(--line);border-radius:14px}
.notice{margin:14px 0;padding:12px 14px;border-radius:10px;border:1px solid #36506f;background:#142033}
.notice.good{border-color:#327a4b;background:#13271a;color:#a8efbf}
.notice.bad{border-color:#983f3f;background:#2a1515;color:#ffb0b0}
.pass{color:var(--good);font-weight:800}.fail{color:var(--bad);font-weight:800}.muted{color:var(--muted)}
code{background:#282828;padding:2px 5px;border-radius:5px;word-break:break-all}
.buttons{display:flex;flex-wrap:wrap;gap:10px;margin-top:14px}
button,.btn{border:0;border-radius:9px;padding:10px 16px;color:white;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;font-size:14px}
.stage{background:#248c4b}.open{background:#276fca}
pre{white-space:pre-wrap;word-break:break-word;background:#111;border:1px solid #333;border-radius:10px;padding:12px}
</style>
</head>
<body>
<div class="wrap">
    <h1>MRL Vendor Quarantine Lint Gate</h1>
    <div class="muted">Generated 9/20/2026 2:25:01 pm ET</div>

    <?php if ($message !== ''): ?>
        <div class="notice <?php echo h($messageClass); ?>"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Real installer</h2>
        <p><code><?php echo h(REAL_FILE_NAME); ?></code></p>
        <p class="muted">Stages the exact reversible quarantine installer, verifies SHA-256, and runs <code>php -l</code>.</p>
    </div>

    <div class="card">
        <h2>Lint status</h2>
        <?php if ($ready): ?>
            <p class="pass">PASS — installer is staged, hash-matched, and lint-clean.</p>
        <?php elseif ($lint !== null): ?>
            <p class="fail">NOT READY — review lint result.</p>
        <?php else: ?>
            <p class="muted">Not staged yet.</p>
        <?php endif; ?>

        <?php if ($lint !== null): ?>
            <pre><?php echo h((string)$lint['output']); ?></pre>
        <?php endif; ?>

        <div class="buttons">
            <form method="post">
                <input type="hidden" name="action" value="stage">
                <button class="stage" type="submit">Stage + Lint Real Installer</button>
            </form>
            <?php if ($ready): ?>
                <a class="btn open" href="<?php echo h(REAL_FILE_NAME); ?>">Open Real Installer</a>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
